<?php

namespace Tests\Feature;

use App\Models\AgendaOs;
use App\Models\OpTask;
use App\Models\OsTecnico;
use App\Models\Tecnico;
use App\Models\TecnicoIndisponibilidade;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\OpTaskService;
use App\Services\OsTecnicoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgendaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_agenda_por_data_e_regiao(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-20', '09:00', '10:00');
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->getJson('/api/agenda?data=2026-07-20&regiao=Vale%20do%20A%C3%A7o&visao=diaria')
            ->assertOk()
            ->assertJsonFragment(['id' => $agenda->id])
            ->assertJsonFragment(['nome' => $tecnico->nome]);
    }

    public function test_impede_movimento_que_causa_conflito(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-20', '09:00', '10:00');
        $this->criarAgenda('Vale do Aço', '2026-07-20', '10:00', '11:00', $tecnico);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->putJson("/api/agenda/{$agenda->id}/mover", [
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-20',
            'hora_inicio' => '10:30',
        ])->assertUnprocessable()->assertJsonPath('message', 'Este período conflita com outra atividade do técnico.');
    }

    public function test_programa_uma_os_disponivel(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-20', '09:00', '10:00');
        $os = $agenda->osTecnico;
        $agenda->delete();
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->postJson('/api/agenda', [
            'os_tecnico_id' => $os->id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-21',
            'hora_inicio' => '08:00',
            'hora_fim' => '09:30',
        ])->assertCreated()->assertJsonPath('os_tecnico_id', $os->id);

        $this->assertTrue(AgendaOs::query()
            ->where('os_tecnico_id', $os->id)
            ->whereDate('data', '2026-07-21')
            ->exists());
    }

    public function test_nao_programa_a_mesma_os_duas_vezes(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-20', '09:00', '10:00');
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->postJson('/api/agenda', [
            'os_tecnico_id' => $agenda->os_tecnico_id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-22',
            'hora_inicio' => '08:00',
            'hora_fim' => '09:00',
        ])->assertUnprocessable()->assertJsonPath('message', 'Esta OS já possui uma programação na agenda.');
    }

    public function test_mover_card_reatribui_a_tarefa_filha_e_a_contagem(): void
    {
        [$agenda, $tecnicoAntigo] = $this->criarAgenda('Vale do Aço', '2026-07-20', '09:00', '10:00');
        $tecnicoNovo = Tecnico::create(['nome' => 'Carlos', 'regiao' => 'Vale do Aço']);
        $taskId = $agenda->osTecnico->task_id;
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->putJson("/api/agenda/{$agenda->id}/mover", [
            'tecnico_id' => $tecnicoNovo->id,
            'data' => '2026-07-20',
            'hora_inicio' => '11:00',
        ])->assertOk()->assertJsonPath('tecnico_id', $tecnicoNovo->id);

        $this->assertDatabaseHas('op_tasks', ['id' => $taskId, 'responsavel' => 'Carlos']);
        $this->assertDatabaseHas('os_tecnicos', ['task_id' => $taskId, 'tecnico_nome' => 'Carlos']);
        $this->assertDatabaseMissing('os_tecnicos', ['task_id' => $taskId, 'tecnico_nome' => $tecnicoAntigo->nome]);
        $this->assertDatabaseHas('agenda_os', ['id' => $agenda->id, 'tecnico_id' => $tecnicoNovo->id]);
    }

    public function test_programa_os_automaticamente_arredondando_para_meia_hora_anterior(): void
    {
        [$os, $tecnico] = $this->criarOsPendente('Tecnico Auto');

        $agenda = app(AgendaService::class)->programarAutomaticamente(
            $os,
            Carbon::parse('2026-07-21 11:14:48', 'America/Sao_Paulo'),
        );

        $this->assertNotNull($agenda);
        $this->assertSame($tecnico->id, $agenda->tecnico_id);
        $this->assertSame('2026-07-21', $agenda->data->toDateString());
        $this->assertSame('11:00:00', $agenda->hora_inicio);
        $this->assertSame('12:00:00', $agenda->hora_fim);
    }

    public function test_programacao_automatica_procura_proximo_horario_livre(): void
    {
        [$os, $tecnico] = $this->criarOsPendente('Tecnico Conflito');
        $this->criarAgenda('Vale do Aço', '2026-07-21', '11:00', '12:00', $tecnico);

        $agenda = app(AgendaService::class)->programarAutomaticamente(
            $os,
            Carbon::parse('2026-07-21 11:14:00', 'America/Sao_Paulo'),
        );

        $this->assertNotNull($agenda);
        $this->assertSame('12:00:00', $agenda->hora_inicio);
        $this->assertSame('13:00:00', $agenda->hora_fim);
    }

    public function test_os_com_dois_tecnicos_programa_um_card_para_cada_responsavel(): void
    {
        [$os] = $this->criarOsPendente('Tecnico Um');
        Tecnico::create(['nome' => 'Tecnico Dois', 'regiao' => 'Vale do Aço']);
        $os = app(OpTaskService::class)->updateOpTask($os, [
            'responsavel' => 'Tecnico Um, Tecnico Dois',
        ]);

        $agenda = app(AgendaService::class)->programarAutomaticamente(
            $os->fresh(),
            Carbon::parse('2026-07-21 11:14:00', 'America/Sao_Paulo'),
        );

        $this->assertNotNull($agenda);
        $this->assertCount(2, OpTask::parseResponsaveis($os->fresh()->responsavel));
        $this->assertSame(2, AgendaOs::query()
            ->whereIn('os_tecnico_id', OsTecnico::query()->where('task_id', $os->id)->select('id'))
            ->count());
        $this->assertSame(2, OsTecnico::query()->where('task_id', $os->id)->count());
    }

    public function test_mover_um_dos_dois_cards_preserva_a_contagem_e_sincroniza_o_horario(): void
    {
        [$os] = $this->criarOsPendente('Tecnico Um');
        Tecnico::create(['nome' => 'Tecnico Dois', 'regiao' => 'Vale do Aço']);
        $novoTecnico = Tecnico::create(['nome' => 'Tecnico Tres', 'regiao' => 'Vale do Aço']);
        $os = app(OpTaskService::class)->updateOpTask($os, [
            'responsavel' => 'Tecnico Um, Tecnico Dois',
        ]);
        app(AgendaService::class)->programarAutomaticamente(
            $os,
            Carbon::parse('2026-07-21 11:14:00', 'America/Sao_Paulo'),
        );
        $agenda = AgendaOs::query()
            ->whereHas('osTecnico', fn ($query) => $query
                ->where('task_id', $os->id)
                ->where('tecnico_nome', 'Tecnico Um'))
            ->sole();
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->putJson("/api/agenda/{$agenda->id}/mover", [
            'tecnico_id' => $novoTecnico->id,
            'data' => '2026-07-22',
            'hora_inicio' => '13:00',
        ])->assertOk();

        $responsaveis = OpTask::parseResponsaveis($os->fresh()->responsavel);
        $this->assertCount(2, $responsaveis);
        $this->assertContains('Tecnico Dois', $responsaveis);
        $this->assertContains('Tecnico Tres', $responsaveis);
        $this->assertNotContains('Tecnico Um', $responsaveis);
        $this->assertSame(2, AgendaOs::query()
            ->whereIn('os_tecnico_id', OsTecnico::query()->where('task_id', $os->id)->select('id'))
            ->whereDate('data', '2026-07-22')
            ->where('hora_inicio', '13:00:00')
            ->where('hora_fim', '14:00:00')
            ->count());
    }

    public function test_criar_tarefa_filha_dispara_programacao_automatica(): void
    {
        Tecnico::create(['nome' => 'Tecnico Integracao', 'regiao' => 'Vale do Aço']);
        $parentId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'VL-ROM-900',
            'titulo' => 'Rompimento de integração',
            'responsavel' => '',
            'categoria' => 'rompimentos',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-07-21 11:14:00', 'America/Sao_Paulo'));

        try {
            $os = app(OpTaskService::class)->createOpTask([
                'titulo' => 'OS criada pelo fluxo real',
                'responsavel' => 'Tecnico Integracao',
                'categoria' => 'ordem-servico',
                'regiao' => 'Vale do Aço',
                'parent_task_id' => $parentId,
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertTrue($os->agendaConfigurada());
        $this->assertDatabaseHas('agenda_os', ['hora_inicio' => '11:00:00', 'hora_fim' => '12:00:00']);
    }

    public function test_os_sem_tecnico_aparece_pendente_e_recebe_responsavel_ao_programar(): void
    {
        $parentId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'VL-ROM-002',
            'titulo' => 'Rompimento sem técnico',
            'responsavel' => '',
            'regiao' => 'Vale do Aço',
            'categoria' => 'rompimentos',
        ]);
        $taskId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'XX-OS-006',
            'titulo' => 'CTO PENDURADA',
            'responsavel' => '',
            'regiao' => '',
            'categoria' => 'ordem-servico',
            'parent_task_id' => $parentId,
        ]);
        $os = OpTask::findOrFail($taskId);
        app(OsTecnicoService::class)->sincronizarParaOs($os);
        $espelho = OsTecnico::query()->where('task_id', $os->id)->sole();
        $this->assertSame('', $espelho->tecnico_nome);
        $this->assertSame('Vale do Aço', $espelho->regiao);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->getJson('/api/agenda-ordens-disponiveis?regiao=Vale%20do%20A%C3%A7o')
            ->assertOk()
            ->assertJsonFragment(['task_code' => 'XX-OS-006']);

        $this->getJson('/api/agenda-ordens-disponiveis?regiao=Vale%20do%20A%C3%A7o&busca=PENDURADA')
            ->assertOk()
            ->assertJsonFragment(['task_code' => 'XX-OS-006']);

        $this->getJson('/api/agenda-ordens-disponiveis?regiao=Vale%20do%20A%C3%A7o&busca=inexistente-xyz')
            ->assertOk()
            ->assertJsonCount(0, 'ordens');

        $this->getJson('/api/agenda-ordens-disponiveis?regiao=Vale%20do%20A%C3%A7o&ordem=antigas')
            ->assertOk()
            ->assertJsonFragment(['task_code' => 'XX-OS-006']);

        $tecnico = Tecnico::create(['nome' => 'Carlos Agenda', 'regiao' => 'Vale do Aço']);
        $this->postJson('/api/agenda', [
            'os_tecnico_id' => $espelho->id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-23',
            'hora_inicio' => '14:00',
            'hora_fim' => '15:00',
        ])->assertCreated();

        $this->assertDatabaseHas('op_tasks', ['id' => $os->id, 'responsavel' => 'Carlos Agenda']);
        $this->assertDatabaseHas('os_tecnicos', ['id' => $espelho->id, 'tecnico_nome' => 'Carlos Agenda']);
    }

    public function test_editar_tecnico_na_tarefa_filha_move_a_os_na_agenda(): void
    {
        [$agenda] = $this->criarAgenda('Vale do Aço', '2026-07-23', '14:00', '15:00');
        $os = $agenda->osTecnico->task;
        $novoTecnico = Tecnico::create(['nome' => 'Outro técnico', 'regiao' => 'Vale do Aço']);
        $os->update(['categoria' => 'ordem-servico']);

        app(OpTaskService::class)->updateOpTask($os->fresh(), [
            'titulo' => 'Título atualizado',
            'status' => 'Em andamento',
            'responsavel' => $novoTecnico->nome,
        ]);

        $this->assertDatabaseHas('agenda_os', [
            'id' => $agenda->id,
            'os_tecnico_id' => $agenda->os_tecnico_id,
            'tecnico_id' => $novoTecnico->id,
        ]);
        $this->assertDatabaseHas('os_tecnicos', [
            'id' => $agenda->os_tecnico_id,
            'titulo' => 'Título atualizado',
            'status' => 'Em andamento',
            'tecnico_nome' => $novoTecnico->nome,
        ]);
        $this->assertDatabaseHas('op_tasks', [
            'id' => $os->id,
            'responsavel' => $novoTecnico->nome,
        ]);
    }

    public function test_conflito_impede_troca_na_tarefa_filha_e_desfaz_a_edicao(): void
    {
        [$agenda, $tecnicoAtual] = $this->criarAgenda('Vale do Aço', '2026-07-23', '14:00', '15:00');
        $novoTecnico = Tecnico::create(['nome' => 'Técnico ocupado', 'regiao' => 'Vale do Aço']);
        $this->criarAgenda('Vale do Aço', '2026-07-23', '14:00', '15:00', $novoTecnico);
        $os = $agenda->osTecnico->task;
        $os->update(['categoria' => 'ordem-servico']);

        try {
            app(OpTaskService::class)->updateOpTask($os->fresh(), [
                'responsavel' => $novoTecnico->nome,
            ]);
            $this->fail('A troca deveria ter sido bloqueada pelo conflito de horário.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'O técnico selecionado já possui outra atividade nesse horário.',
                $e->validator->errors()->first('responsavel'),
            );
        }

        $this->assertDatabaseHas('op_tasks', [
            'id' => $os->id,
            'responsavel' => $tecnicoAtual->nome,
        ]);
        $this->assertDatabaseHas('agenda_os', [
            'id' => $agenda->id,
            'tecnico_id' => $tecnicoAtual->id,
        ]);
    }

    public function test_impede_programar_dois_espelhos_da_mesma_tarefa(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-23', '14:00', '15:00');
        $outroEspelho = OsTecnico::create([
            'task_id' => $agenda->osTecnico->task_id,
            'tecnico_nome' => 'Outro técnico',
            'ordem_servico' => $agenda->osTecnico->ordem_servico,
            'titulo' => $agenda->osTecnico->titulo,
        ]);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->postJson('/api/agenda', [
            'os_tecnico_id' => $outroEspelho->id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-24',
            'hora_inicio' => '10:00',
            'hora_fim' => '11:00',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Este técnico já está vinculado a esta OS.');

        $this->assertDatabaseCount('agenda_os', 1);
    }

    public function test_lista_apenas_tecnicos_com_usuario_cadastrado(): void
    {
        $this->criarTecnicoCadastrado('Tec Goval', 'Goval');
        $this->criarTecnicoCadastrado('Tec GV', 'Governador Valadares');
        $this->criarTecnicoCadastrado('Tec Vale', 'Vale do Aço');
        Tecnico::create(['nome' => 'Orfao Sem User', 'regiao' => 'Goval']); // sem username / usuario
        Sanctum::actingAs(new User(['username' => 'teste']));

        $resposta = $this->getJson('/api/agenda?data=2026-07-27&regiao=Goval&visao=diaria')
            ->assertOk();

        $nomes = collect($resposta->json('tecnicos'))->pluck('nome');
        $this->assertContains('Tec Goval', $nomes);
        $this->assertContains('Tec GV', $nomes);
        $this->assertNotContains('Tec Vale', $nomes);
        $this->assertNotContains('Orfao Sem User', $nomes);
        $this->assertEqualsCanonicalizing(
            $nomes->all(),
            collect($resposta->json('tecnicos_regiao'))->pluck('nome')->all()
        );
    }

    public function test_tecnico_indisponivel_aparece_bloqueado_na_visao_diaria(): void
    {
        $tecnico = $this->criarTecnicoCadastrado('Técnico de férias', 'Vale do Aço');
        TecnicoIndisponibilidade::create([
            'tecnico_id' => $tecnico->id,
            'motivo' => 'ferias',
            'data_inicio' => '2026-07-24',
            'data_fim' => '2026-07-31',
        ]);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $resposta = $this->getJson('/api/agenda?data=2026-07-24&regiao=Vale%20do%20A%C3%A7o&visao=diaria')
            ->assertOk();

        $tecnicoNoPainel = collect($resposta->json('tecnicos'))->firstWhere('nome', 'Técnico de férias');
        $this->assertNotNull($tecnicoNoPainel);
        $this->assertSame('ferias', $tecnicoNoPainel['indisponibilidades'][0]['motivo']);
    }

    public function test_tecnico_indisponivel_com_agenda_continua_visivel_para_reorganizacao(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-24', '09:00', '10:00');
        TecnicoIndisponibilidade::create([
            'tecnico_id' => $tecnico->id,
            'motivo' => 'atestado',
            'data_inicio' => '2026-07-24',
            'data_fim' => '2026-07-24',
        ]);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->getJson('/api/agenda?data=2026-07-24&regiao=Vale%20do%20A%C3%A7o&visao=diaria')
            ->assertOk()
            ->assertJsonFragment(['id' => $agenda->id])
            ->assertJsonFragment(['nome' => $tecnico->nome]);
    }

    public function test_nao_permite_novo_agendamento_para_tecnico_indisponivel(): void
    {
        [$agenda, $tecnico] = $this->criarAgenda('Vale do Aço', '2026-07-23', '09:00', '10:00');
        $os = $agenda->osTecnico;
        $agenda->delete();
        TecnicoIndisponibilidade::create([
            'tecnico_id' => $tecnico->id,
            'motivo' => 'folga',
            'data_inicio' => '2026-07-24',
            'data_fim' => '2026-07-24',
        ]);
        Sanctum::actingAs(new User(['username' => 'teste']));

        $this->postJson('/api/agenda', [
            'os_tecnico_id' => $os->id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-24',
            'hora_inicio' => '09:00',
            'hora_fim' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'O técnico está indisponível nesta data.');
    }

    private function criarAgenda(string $regiao, string $data, string $inicio, string $fim, ?Tecnico $tecnico = null): array
    {
        $tecnico ??= $this->criarTecnicoCadastrado('Eduardo', $regiao);
        $taskId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'VL-ATD-'.random_int(1000, 9999),
            'titulo' => 'Atendimento de teste',
            'responsavel' => $tecnico->nome,
            'categoria' => 'Atendimento',
        ]);
        $os = OsTecnico::create(['task_id' => $taskId, 'tecnico_nome' => $tecnico->nome, 'ordem_servico' => 'OS-'.$taskId, 'titulo' => 'Atividade']);
        $agenda = AgendaOs::create(['os_tecnico_id' => $os->id, 'tecnico_id' => $tecnico->id, 'data' => $data, 'hora_inicio' => $inicio, 'hora_fim' => $fim]);

        return [$agenda, $tecnico];
    }

    private function criarTecnicoCadastrado(string $nome, string $regiao): Tecnico
    {
        $username = preg_replace('/\s+/', '', $nome) ?: 'tec'.random_int(100, 999);

        DB::table('usuario')->updateOrInsert(
            ['username' => $username],
            [
                'pass_salt' => str_repeat('a', 64),
                'pass_hash' => str_repeat('b', 64),
                'pass_iterations' => 200000,
                'created_at' => now(),
            ]
        );

        return Tecnico::query()->updateOrCreate(
            ['username' => $username],
            ['nome' => $nome, 'regiao' => $regiao]
        );
    }

    private function criarOsPendente(string $nomeTecnico): array
    {
        $tecnico = $this->criarTecnicoCadastrado($nomeTecnico, 'Vale do Aço');
        $taskId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'VL-OS-'.random_int(1000, 9999),
            'titulo' => 'OS automática',
            'responsavel' => $nomeTecnico,
            'categoria' => 'ordem-servico',
        ]);
        $os = OpTask::findOrFail($taskId);
        OsTecnico::create([
            'task_id' => $os->id,
            'tecnico_nome' => $nomeTecnico,
            'task_code' => $os->taskCode,
            'titulo' => $os->titulo,
            'regiao' => 'Vale do Aço',
        ]);

        return [$os, $tecnico];
    }
}
