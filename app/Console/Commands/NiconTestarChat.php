<?php

namespace App\Console\Commands;

use App\Services\Nicon\NiconWebService;
use Illuminate\Console\Command;

class NiconTestarChat extends Command
{
    protected $signature = 'nicon:testar-chat
        {conversaId : ID da conversa (ex.: 4180)}
        {mensagem? : Texto da mensagem}
        {--thread= : ID da mensagem raiz para responder no tópico (POST .../replies)}
        {--fluxo : Envia pai + reply no tópico e mostra o mapeamento para migração}
        {--listar-thread= : Lista o tópico de uma mensagem raiz (GET .../thread)}';

    protected $description = 'Testa envio de mensagem Nicon (pai, tópico/replies ou fluxo completo de migração)';

    public function handle(NiconWebService $nicon): int
    {
        $conversaId = (int) $this->argument('conversaId');
        $listarThread = $this->option('listar-thread');

        if ($listarThread !== null) {
            $resultado = $nicon->listarThread($conversaId, (int) $listarThread);
            $this->info("Thread da mensagem #{$listarThread}:");
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $mensagem = (string) ($this->argument('mensagem') ?? '');
        if ($mensagem === '' && ! $this->option('fluxo')) {
            $this->error('Informe a mensagem, ou use --fluxo / --listar-thread.');

            return self::FAILURE;
        }

        if ($this->option('fluxo')) {
            return $this->rodarFluxoMigracao($nicon, $conversaId, $mensagem !== '' ? $mensagem : 'Planner fluxo migração');
        }

        $idThread = $this->option('thread');

        if ($idThread !== null) {
            $resultado = $nicon->enviarMensagemThread($conversaId, (int) $idThread, $mensagem);
            $this->info("Enviado na thread da mensagem #{$idThread}:");
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->mostrarDicasThread($resultado);

            return self::SUCCESS;
        }

        $resultado = $nicon->enviarMensagemChat($conversaId, $mensagem);
        $this->info('Enviado com sucesso (mensagem pai):');
        $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $idMsg = $resultado['id_chat_mensagem'] ?? null;
        if ($idMsg) {
            $this->newLine();
            $this->comment("Para responder no tópico:");
            $this->line("  php artisan nicon:testar-chat {$conversaId} \"resposta\" --thread={$idMsg}");
        }

        return self::SUCCESS;
    }

    private function rodarFluxoMigracao(NiconWebService $nicon, int $conversaId, string $base): int
    {
        $this->info('1) Mensagem pai (equivalente à 1ª notificação Google Chat)...');
        $pai = $nicon->enviarMensagemChat($conversaId, "{$base} — PAI");
        $idMsgRaiz = (int) ($pai['id_chat_mensagem'] ?? 0);

        if ($idMsgRaiz <= 0) {
            $this->error('Resposta pai sem id_chat_mensagem.');
            $this->line(json_encode($pai, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $this->line("   id_chat_mensagem (raiz) = {$idMsgRaiz}");

        $this->info('2) Reply no tópico (POST .../replies) — cria o subtópico se não existir...');
        $reply = $nicon->enviarMensagemThread($conversaId, $idMsgRaiz, "{$base} — REPLY");
        $idChatTopico = (int) data_get($reply, 'thread.id_chat', 0);

        $this->line(json_encode($reply, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($idChatTopico > 0) {
            $this->info("3) Atualização seguinte via chat do tópico (id_chat={$idChatTopico})...");
            $update = $nicon->enviarMensagemChat($idChatTopico, "{$base} — UPDATE direto no topico");
            $this->line(json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->newLine();
        $this->info('Mapeamento sugerido (Google Chat → Nicon):');
        $this->table(
            ['Google Chat', 'Nicon (guardar)'],
            [
                ['webhook URL por região', "id_chat da conversa (= {$conversaId})"],
                ['1ª msg → thread.name', "id_chat_mensagem raiz (= {$idMsgRaiz})"],
                ['respostas na thread', 'POST .../messages/{raiz}/replies'],
                ['OU thread.name reutilizado', "id_chat do tópico (= {$idChatTopico}) + POST .../messages"],
                ['chat_thread_key hoje', 'nicon_mensagem_raiz_id e/ou nicon_thread_chat_id'],
            ]
        );

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $resultado */
    private function mostrarDicasThread(array $resultado): void
    {
        $idChatTopico = data_get($resultado, 'thread.id_chat');
        $idRaiz = data_get($resultado, 'thread.id_chat_mensagem_raiz');
        $idPai = data_get($resultado, 'thread.id_chat_pai');

        if (! $idChatTopico) {
            return;
        }

        $this->newLine();
        $this->comment('IDs do tópico (úteis para migração):');
        $this->line("  thread.id_chat (chat do tópico)     = {$idChatTopico}");
        $this->line("  thread.id_chat_mensagem_raiz        = {$idRaiz}");
        $this->line("  thread.id_chat_pai (conversa)       = {$idPai}");
        $this->line("  Próximas msgs: artisan nicon:testar-chat {$idChatTopico} \"...\"");
        $this->line("  Ou: artisan nicon:testar-chat {$idPai} \"...\" --thread={$idRaiz}");
    }
}
