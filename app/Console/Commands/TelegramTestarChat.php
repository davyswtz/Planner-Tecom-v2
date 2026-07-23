<?php

namespace App\Console\Commands;

use App\Models\OpTask;
use App\Services\GoogleChatService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramChatNotificacaoService;
use Illuminate\Console\Command;

class TelegramTestarChat extends Command
{
    protected $signature = 'telegram:testar-chat
        {mensagem? : Texto da mensagem}
        {--regiao=Teste : Região (resolve chat_id via config)}
        {--chat= : chat_id explícito (sobrescreve região)}
        {--reply= : message_id da mensagem pai para comentar}
        {--mencionar= : Telegram user_id para testar menção no comentário}
        {--username= : Telegram @username (preferível para notificar)}
        {--simular-drag : Simula notificação de arraste (mesmo fluxo Google/Nicon)}
        {--fluxo-thread : Post no canal + comentário de OS (reply na discussão)}
        {--fluxo-topico : Alias de --fluxo-thread (legado)}
        {--enviar-anexo= : ID do op_task_anexos para testar envio de imagem}
        {--os= : ID da OS (usa fluxo real GoogleChat::enviarNovoAnexoOsNoChat; --task=pai opcional)}
        {--status=Em andamento : Status novo no --simular-drag / --fluxo-thread}
        {--task= : ID da tarefa pai (obrigatório no --fluxo-thread; opcional com --os)}';

    protected $description = 'Testa envio Telegram (post no canal, comentários/OS, anexos ou simulação de arraste)';

    public function handle(
        TelegramBotService $telegram,
        TelegramChatNotificacaoService $telegramChat,
        GoogleChatService $googleChat,
    ): int {
        if ($this->option('enviar-anexo') !== null && $this->option('enviar-anexo') !== '') {
            return $this->enviarAnexoTeste($telegramChat, $googleChat);
        }

        if ($this->option('mencionar') !== null && $this->option('mencionar') !== '') {
            return $this->testarMencao($telegram);
        }

        if ($this->option('fluxo-thread') || $this->option('fluxo-topico')) {
            return $this->fluxoThread($telegramChat, $googleChat);
        }

        if ($this->option('simular-drag')) {
            return $this->simularDrag($telegramChat, $googleChat);
        }

        $mensagem = (string) ($this->argument('mensagem') ?? 'Planner Tecom — teste Telegram');
        $chatOpt = $this->option('chat');
        $regiao = (string) $this->option('regiao');

        $chatId = $chatOpt !== null && $chatOpt !== ''
            ? (is_numeric($chatOpt) ? (int) $chatOpt : (string) $chatOpt)
            : $this->resolverChatId($regiao);

        if ($chatId === null) {
            $this->error("chat_id não configurado para região \"{$regiao}\".");
            $this->line('Defina TELEGRAM_CHAT_IDS ou use --chat=-100...');

            return self::FAILURE;
        }

        $reply = $this->option('reply');
        $replyId = $reply !== null && $reply !== '' ? (int) $reply : null;

        $this->info('Enviando para Telegram...');
        $this->line('  chat_id: '.$chatId);
        $this->line('  região:  '.$regiao);
        if ($replyId) {
            $this->line("  reply_to_message_id: {$replyId}");
        }

        $resultado = $telegram->enviarMensagem($chatId, $mensagem, null, $replyId);

        $this->info('Enviado com sucesso:');
        $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $msgId = (int) ($resultado['message_id'] ?? 0);
        if ($msgId > 0 && ! $replyId) {
            $this->newLine();
            $this->comment('Para comentar nesta mensagem:');
            $this->line("  php artisan telegram:testar-chat \"comentário\" --chat={$chatId} --reply={$msgId}");
        }

        return self::SUCCESS;
    }

    private function testarMencao(TelegramBotService $telegram): int
    {
        $userId = (int) $this->option('mencionar');
        if ($userId <= 0) {
            $this->error('Informe --mencionar=USER_ID válido.');

            return self::FAILURE;
        }

        $username = ltrim(trim((string) ($this->option('username') ?? '')), '@');
        $regiao = (string) $this->option('regiao');
        $channelCfg = config('services.telegram.chat_ids', []);
        $discussionCfg = config('services.telegram.discussion_chat_ids', []);
        $channelId = $channelCfg[$regiao] ?? $channelCfg['Teste'] ?? null;
        $discussionId = $discussionCfg[$regiao] ?? $discussionCfg['Teste'] ?? null;

        if ($channelId === null || $discussionId === null) {
            $this->error('Canal/discussão não configurados para a região.');

            return self::FAILURE;
        }

        $this->info('1) Post pai no canal...');
        $pai = $telegram->enviarMensagem($channelId, '<b>TESTE MENÇÃO</b> — Planner Tecom');
        $channelMsgId = (int) ($pai['message_id'] ?? 0);
        $this->line("   channel message_id={$channelMsgId}");

        $this->info('2) Aguardando forward na discussão...');
        $discMsgId = $telegram->aguardarMensagemDiscussao($channelId, $channelMsgId, $discussionId, 20, 500);
        if (! $discMsgId) {
            $this->error('Não achou o forward no grupo de discussão.');

            return self::FAILURE;
        }
        $this->line("   discussion message_id={$discMsgId}");

        // Forma que notifica: @username quando existir; senão tg://user?id=
        $mencao = TelegramBotService::formatarMencao($userId, 'Usuário', $username !== '' ? $username : null);
        $texto = implode("\n", [
            '🔔 <b>Teste de menção</b>',
            'Marcando: '.$mencao,
            '',
            'Se tiver username, use @username (notifica).',
            'Senão, usa link tg://user?id= (text_mention).',
        ]);

        // Se não passou username, tenta descobrir via getChat
        if ($username === '') {
            try {
                $chat = $telegram->getChat($userId);
                $username = ltrim((string) ($chat['username'] ?? ''), '@');
                $nome = trim((string) ($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''));
                if ($username !== '') {
                    $mencao = TelegramBotService::formatarMencao($userId, $nome !== '' ? $nome : 'Usuário', $username);
                    $texto = implode("\n", [
                        '🔔 <b>Teste de menção</b>',
                        'Marcando: '.$mencao,
                        "ID: {$userId} · username: @{$username}",
                    ]);
                } elseif ($nome !== '') {
                    $mencao = TelegramBotService::formatarMencao($userId, $nome, null);
                    $texto = implode("\n", [
                        '🔔 <b>Teste de menção</b>',
                        'Marcando: '.$mencao,
                        "ID: {$userId} (sem username público)",
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn('getChat(user) falhou (normal se o user não iniciou o bot): '.$e->getMessage());
            }
        }

        $this->info('3) Comentário com menção...');
        $this->line('   texto: '.$texto);
        $res = $telegram->enviarMensagem($discussionId, $texto, null, $discMsgId);

        $this->info('Enviado.');
        $this->line(json_encode([
            'message_id' => $res['message_id'] ?? null,
            'text' => $res['text'] ?? null,
            'entities' => $res['entities'] ?? null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $tipos = collect($res['entities'] ?? [])->pluck('type')->all();
        if (in_array('mention', $tipos, true)) {
            $this->comment('Entity "mention" (@username) — deve notificar.');
        } elseif (in_array('text_mention', $tipos, true)) {
            $this->comment('Entity "text_mention" (por ID) — clicável; notificação depende da privacidade.');
        } else {
            $this->warn('Nenhuma entity de menção na resposta — a pessoa pode não ter sido marcada.');
        }

        return self::SUCCESS;
    }

    private function enviarAnexoTeste(TelegramChatNotificacaoService $telegramChat, GoogleChatService $googleChat): int
    {
        if (! config('services.telegram.chat_enabled')) {
            $this->error('TELEGRAM_CHAT_ENABLED=false — ative no .env');

            return self::FAILURE;
        }

        $anexoId = (int) $this->option('enviar-anexo');
        $anexo = \App\Models\OpTaskAnexo::find($anexoId);
        if (! $anexo) {
            $this->error("Anexo #{$anexoId} não encontrado.");

            return self::FAILURE;
        }

        $osOpt = $this->option('os');
        if ($osOpt !== null && $osOpt !== '') {
            return $this->enviarAnexoOsReal($googleChat, $anexo, (int) $osOpt);
        }

        $taskOpt = $this->option('task');
        if ($taskOpt === null || $taskOpt === '') {
            $this->error('Informe --task=ID da tarefa pai, ou --os=ID da OS (fluxo real).');

            return self::FAILURE;
        }

        $pai = OpTask::find((int) $taskOpt);
        if (! $pai) {
            $this->error("Tarefa #{$taskOpt} não encontrada.");

            return self::FAILURE;
        }

        $binario = app(\App\Services\OpTaskAnexoService::class)->binarioParaNicon($anexo);
        if ($binario === null) {
            $this->error('Anexo sem conteúdo binário válido.');

            return self::FAILURE;
        }

        $mensagem = [
            'text' => "📎 *Teste de anexo no comentário do canal*\nArquivo: {$binario['nome_arquivo']}",
            'nicon_anexos' => [$binario],
            'regiao' => $pai->regiao,
        ];

        $this->info("Enviando anexo #{$anexoId} como comentário do post da tarefa #{$pai->id}...");
        $this->line('  arquivo: '.$binario['nome_arquivo']);
        $this->line('  mime:    '.$binario['mime_type']);
        $this->line('  bytes:   '.strlen($binario['conteudo']));
        $this->line('  canal msg: '.($pai->telegram_message_id ?? '—'));
        $this->line('  discussão: '.($pai->telegram_topic_id ?? 'será resolvido'));

        $telegramChat->enviarNotificacao($pai, $mensagem, null);

        $this->info('Enviado. Confira a foto nos comentários do post no canal.');

        return self::SUCCESS;
    }

    private function enviarAnexoOsReal(GoogleChatService $googleChat, \App\Models\OpTaskAnexo $anexo, int $osId): int
    {
        $os = OpTask::find($osId);
        if (! $os) {
            $this->error("OS #{$osId} não encontrada.");

            return self::FAILURE;
        }

        if (($os->categoria ?? '') !== 'ordem-servico' || empty($os->parent_task_id)) {
            $this->error("Tarefa #{$osId} não é OS com parent_task_id.");

            return self::FAILURE;
        }

        // Garante vínculo anexo → OS (fluxo real valida ownership).
        if ((int) $anexo->op_task_id !== (int) $os->id) {
            $this->warn("Anexo #{$anexo->id} pertence à task #{$anexo->op_task_id} — clonando para OS #{$os->id}...");
            $anexo = \App\Models\OpTaskAnexo::create([
                'op_task_id' => $os->id,
                'nome_arquivo' => $anexo->nome_arquivo,
                'mime_type' => $anexo->mime_type,
                'tamanho_bytes' => $anexo->tamanho_bytes,
                'conteudo_base64' => $anexo->conteudo_base64,
                'enviado_por' => $anexo->enviado_por ?? 'telegram-teste',
                'criado_em' => now(),
            ]);
            $this->line("  novo anexo #{$anexo->id} vinculado à OS #{$os->id}");
        }

        $pai = OpTask::find((int) $os->parent_task_id);
        if (! $pai) {
            $this->error("Pai #{$os->parent_task_id} da OS não encontrado.");

            return self::FAILURE;
        }

        $this->info('Fluxo real: GoogleChatService::enviarNovoAnexoOsNoChat → Telegram canal...');
        $this->line("  OS #{$os->id} — {$os->titulo} ({$os->taskCode})");
        $this->line("  pai #{$pai->id} — canal msg={$pai->telegram_message_id} discussão={$pai->telegram_topic_id}");
        $this->line("  anexo #{$anexo->id} — {$anexo->nome_arquivo} ({$anexo->mime_type})");

        if ((int) ($pai->telegram_topic_id ?? 0) <= 0 && (int) ($pai->telegram_message_id ?? 0) <= 0) {
            $this->warn('Pai sem IDs Telegram — o anexo pode criar um post novo no canal.');
        }

        $googleChat->enviarNovoAnexoOsNoChat($os->fresh(), $anexo->fresh());

        $this->info('Enviado. Confira o comentário com a foto no post do canal.');

        return self::SUCCESS;
    }

    private function fluxoThread(TelegramChatNotificacaoService $telegramChat, GoogleChatService $googleChat): int
    {
        if (! config('services.telegram.chat_enabled')) {
            $this->error('TELEGRAM_CHAT_ENABLED=false — ative no .env');

            return self::FAILURE;
        }

        $taskOpt = $this->option('task');
        if ($taskOpt === null || $taskOpt === '') {
            $this->error('Informe --task=ID da tarefa pai.');

            return self::FAILURE;
        }

        $pai = OpTask::find((int) $taskOpt);
        if (! $pai) {
            $this->error("Tarefa pai #{$taskOpt} não encontrada.");

            return self::FAILURE;
        }

        // Limpa IDs antigos (tópico/forum ou mensagem) para forçar nova mensagem pai.
        \Illuminate\Support\Facades\Cache::forget("telegram_chat_task_{$pai->id}");
        $limpar = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('op_tasks', 'telegram_message_id')) {
            $limpar['telegram_message_id'] = null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('op_tasks', 'telegram_topic_id')) {
            $limpar['telegram_topic_id'] = null;
        }
        if ($limpar !== []) {
            $pai->forceFill($limpar)->save();
            $pai = $pai->fresh();
        }

        $statusPai = (string) $this->option('status');
        $msgPai = $this->montarMensagemSegura($googleChat, $pai, 'Criada', $statusPai);

        $regiaoPai = trim((string) ($pai->regiao ?? '')) ?: 'Teste';
        $channelCfg = config('services.telegram.chat_ids', []);
        $discussionCfg = config('services.telegram.discussion_chat_ids', []);
        $channelShow = $channelCfg[$regiaoPai] ?? $channelCfg['Goval'] ?? $channelCfg['Teste'] ?? null;
        $discussionShow = $discussionCfg[$regiaoPai] ?? $discussionCfg['Goval'] ?? $discussionCfg['Teste'] ?? null;

        $this->info('1) Tarefa pai → post no CANAL...');
        $this->line("   pai #{$pai->id} — {$pai->titulo}");
        $this->line("   região: {$regiaoPai}");
        $this->line('   channel_id: '.json_encode($channelShow));
        $this->line('   discussion: '.json_encode($discussionShow));
        $telegramChat->enviarNotificacao($pai, $msgPai, $statusPai);

        $pai = $pai->fresh() ?? $pai;
        $cached = \Illuminate\Support\Facades\Cache::get("telegram_chat_task_{$pai->id}", []);
        $channelMsgId = (int) ($pai->telegram_message_id ?? $cached['channel_message_id'] ?? 0);
        $discussionMsgId = (int) ($pai->telegram_topic_id ?? $cached['discussion_message_id'] ?? 0);
        $this->line("   telegram_message_id (post canal) = {$channelMsgId}");
        $this->line("   telegram_topic_id (raiz comentários) = {$discussionMsgId}");

        if ($channelMsgId <= 0 || $discussionMsgId <= 0) {
            $this->error('Post no canal não foi criado. Verifique:');
            $this->line('  1) Bot @CentralProjetosBOt é admin do canal Central de Projetos');
            $this->line('  2) Bot está no grupo de discussão vinculado (BACKUP)');
            $this->line('  3) Comentários/discussão estão ativos no canal');
            $this->line('  4) storage/logs/laravel.log (Telegram canal: falha)');

            return self::FAILURE;
        }

        $os = OpTask::query()
            ->where('parent_task_id', $pai->id)
            ->where('categoria', 'ordem-servico')
            ->orderByDesc('id')
            ->first();

        if (! $os) {
            $this->warn('Nenhuma OS filha encontrada — enviando comentário fictício de OS no post.');
            $msgOs = [
                'text' => "📋 *Atualização de Ordem de Serviço*\n━━━━━━━━━━━━━━━━━━━━\n📌 *Tipo:* TESTE\n🔄 *Status da OS:* Em andamento\n🔗 *Tarefa pai:* {$pai->titulo}",
            ];
            $telegramChat->enviarNotificacao($pai, $msgOs, 'Em andamento');
        } else {
            $this->info("2) OS #{$os->id} → comentário no post do canal...");
            $msgOs = $this->montarMensagemOsSegura($googleChat, $os, $pai);
            $telegramChat->enviarNotificacao($pai, $msgOs, 'Em andamento');
        }

        $this->newLine();
        $this->info('Fluxo concluído. No canal deve aparecer o post com N comentários.');
        $this->comment("canal message_id={$channelMsgId} | discussão reply_to={$discussionMsgId}");

        return self::SUCCESS;

        return self::SUCCESS;
    }

    /** @return array{text: string} */
    private function montarMensagemSegura(
        GoogleChatService $googleChat,
        OpTask $tarefa,
        string $statusAnterior,
        string $statusNovo,
    ): array {
        try {
            return $googleChat->montarMensagemStatus($tarefa->toArray(), $statusAnterior, $statusNovo);
        } catch (\Throwable $e) {
            return $this->mensagemFallback($tarefa, $statusAnterior, $statusNovo);
        }
    }

    /** @return array{text: string} */
    private function montarMensagemOsSegura(GoogleChatService $googleChat, OpTask $os, OpTask $pai): array
    {
        $payload = $os->toArray();
        $payload['parent_titulo'] = $pai->titulo;
        $payload['parent_task_code'] = $pai->taskCode;
        $payload['regiao'] = $pai->regiao ?: ($os->regiao ?? '');

        try {
            if ($googleChat->isOsEmAndamento('Em andamento')) {
                return $googleChat->montarMensagemOsEmAndamento($payload);
            }
        } catch (\Throwable) {
            // fallback abaixo
        }

        return [
            'text' => implode("\n", [
                '📋 *Atualização de Ordem de Serviço*',
                '━━━━━━━━━━━━━━━━━━━━',
                '🔗 *Tarefa pai:* '.trim((string) $pai->titulo).' ('.trim((string) $pai->taskCode).')',
                '📌 *Tipo:* '.trim((string) ($os->titulo ?? 'OS')),
                '🔄 *Status da OS:* Em andamento',
                '🔑 *Código OS:* '.trim((string) ($os->taskCode ?? $os->id)),
            ]),
        ];
    }

    private function simularDrag(TelegramChatNotificacaoService $telegramChat, GoogleChatService $googleChat): int
    {
        if (! config('services.telegram.chat_enabled')) {
            $this->error('TELEGRAM_CHAT_ENABLED=false — ative no .env');

            return self::FAILURE;
        }

        $statusNovo = (string) $this->option('status');
        $taskOpt = $this->option('task');
        $regiao = (string) $this->option('regiao');
        $statusAnterior = 'Criada';

        if ($taskOpt !== null && $taskOpt !== '') {
            $tarefa = OpTask::find((int) $taskOpt);
            if (! $tarefa) {
                $this->error("Tarefa #{$taskOpt} não encontrada.");

                return self::FAILURE;
            }

            try {
                $mensagem = $googleChat->montarMensagemStatus(
                    $tarefa->toArray(),
                    $statusAnterior,
                    $statusNovo
                );
            } catch (\Throwable $e) {
                $this->warn('Não montou template via GoogleChat ('.$e->getMessage().'). Usando texto fallback.');
                $mensagem = $this->mensagemFallback($tarefa, $statusAnterior, $statusNovo);
            }
        } else {
            $tarefa = new OpTask([
                'titulo' => 'Simulação arraste — Planner Telegram',
                'regiao' => $regiao !== '' ? $regiao : 'Teste',
                'categoria' => 'rompimentos',
                'status' => $statusNovo,
                'taskCode' => 'TE-ROM-999',
                'descricao' => 'Mensagem de teste do fluxo de arraste (Telegram).',
                'responsavel' => 'Teste',
            ]);
            $tarefa->id = 9_999_001;
            $tarefa->exists = false;
            $mensagem = $this->mensagemFallback($tarefa, $statusAnterior, $statusNovo);
        }

        $this->info('Simulando arraste (mesmo caminho do board → TelegramChatNotificacaoService)...');
        $this->line('  task_id:  '.($tarefa->id ?? '—'));
        $this->line('  região:   '.($tarefa->regiao ?? '—'));
        $this->line('  status:   '.$statusAnterior.' → '.$statusNovo);
        $this->line('  chat_id:  '.json_encode(config('services.telegram.chat_ids.Teste')));
        $this->newLine();
        $this->line((string) ($mensagem['text'] ?? '(sem texto)'));
        $this->newLine();

        $telegramChat->enviarNotificacao($tarefa, $mensagem, $statusNovo);

        $this->info('Notificação Telegram disparada. Confira o grupo.');

        return self::SUCCESS;
    }

    /** @return array{text: string} */
    private function mensagemFallback(OpTask $tarefa, string $statusAnterior, string $statusNovo): array
    {
        $titulo = trim((string) ($tarefa->titulo ?? '')) ?: '—';
        $code = trim((string) ($tarefa->taskCode ?? '')) ?: (string) ($tarefa->id ?? '—');
        $regiao = trim((string) ($tarefa->regiao ?? '')) ?: '—';

        $texto = implode("\n", [
            '🚨 *ROMPIMENTO*',
            '━━━━━━━━━━━━━━━━━━━━',
            "📌 *Título:* {$titulo}",
            "🔑 *Código:* {$code}",
            "🗺 *Região:* {$regiao}",
            "📤 *Status:* {$statusAnterior} → {$statusNovo}",
            '🧪 Simulação de arraste (Telegram / região Teste)',
        ]);

        return ['text' => $texto];
    }

    private function resolverChatId(string $regiao): int|string|null
    {
        $porRegiao = config('services.telegram.chat_ids', []);
        if (! is_array($porRegiao)) {
            $porRegiao = [];
        }

        $chave = mb_strtolower(trim($regiao));
        foreach ($porRegiao as $nome => $id) {
            if (mb_strtolower(trim((string) $nome)) !== $chave) {
                continue;
            }
            if ($id === null || $id === '' || $id === 0 || $id === '0') {
                continue;
            }

            return is_numeric($id) ? (int) $id : (string) $id;
        }

        if (in_array($chave, ['teste', 'test', 'backup'], true)) {
            foreach (['Teste', 'TESTE', 'teste', 'Backup'] as $alias) {
                $id = $porRegiao[$alias] ?? null;
                if ($id !== null && $id !== '' && $id !== 0 && $id !== '0') {
                    return is_numeric($id) ? (int) $id : (string) $id;
                }
            }
        }

        $padrao = config('services.telegram.chat_id');
        if ($padrao === null || $padrao === '' || $padrao === 0 || $padrao === '0') {
            return null;
        }

        return is_numeric($padrao) ? (int) $padrao : (string) $padrao;
    }
}
