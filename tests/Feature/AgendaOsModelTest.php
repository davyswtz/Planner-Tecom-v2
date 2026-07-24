<?php

namespace Tests\Feature;

use App\Models\AgendaOs;
use App\Models\OsTecnico;
use App\Models\Tecnico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgendaOsModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_programacao_se_relaciona_com_os_e_tecnico(): void
    {
        $taskId = DB::table('op_tasks')->insertGetId([
            'taskCode' => 'VL-ATD-1001',
            'titulo' => 'Atendimento de teste',
            'responsavel' => 'Eduardo',
            'categoria' => 'Atendimento',
        ]);

        $osTecnico = OsTecnico::create([
            'task_id' => $taskId,
            'tecnico_nome' => 'Eduardo',
            'ordem_servico' => 'OS-1001',
            'titulo' => 'Troca de CTO',
        ]);

        $tecnico = Tecnico::create([
            'nome' => 'Eduardo',
            'regiao' => 'Vale do Aço',
        ]);

        $programacao = AgendaOs::create([
            'os_tecnico_id' => $osTecnico->id,
            'tecnico_id' => $tecnico->id,
            'data' => '2026-07-20',
            'hora_inicio' => '09:00',
            'hora_fim' => '12:30',
        ]);

        $this->assertTrue($programacao->osTecnico->is($osTecnico));
        $this->assertTrue($programacao->tecnico->is($tecnico));
        $this->assertCount(1, $osTecnico->agenda);
        $this->assertCount(1, $tecnico->agenda);
        $this->assertSame('2026-07-20', $programacao->data->toDateString());
        $this->assertSame('09:00', $programacao->hora_inicio);
        $this->assertSame('12:30', $programacao->hora_fim);
    }
}
