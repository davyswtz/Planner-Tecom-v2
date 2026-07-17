<?php

namespace App\Services;

use App\Models\OpTask;
use App\Models\OpTaskAnexo;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpTaskAnexoService
{
    /** @return Collection<int, OpTaskAnexo> */
    public function listar(OpTask $opTask): Collection
    {
        return OpTaskAnexo::query()
            ->where('op_task_id', $opTask->id)
            ->orderByDesc('id')
            ->get();
    }

    /** @return array<int, array<string, mixed>> */
    public function listarFormatado(OpTask $opTask): array
    {
        return $this->listar($opTask)
            ->map(fn (OpTaskAnexo $anexo) => [
                'id' => $anexo->id,
                'nome_arquivo' => $anexo->nome_arquivo,
                'mime_type' => $anexo->mime_type,
                'tamanho_bytes' => $anexo->tamanho_bytes,
                'enviado_por' => $anexo->enviado_por,
                'criado_em' => optional($anexo->criado_em)->toIso8601String(),
                'url' => url("/api/op-tasks/{$opTask->id}/anexos/{$anexo->id}/arquivo"),
            ])
            ->all();
    }

    public function salvar(
        OpTask $opTask,
        string $nomeArquivo,
        string $mimeType,
        string $conteudoBase64,
        ?string $enviadoPor = null,
    ): OpTaskAnexo {
        $mimeType = $this->normalizarMimeType($mimeType);
        $conteudoBase64 = $this->extrairBase64($conteudoBase64);

        $binario = base64_decode($conteudoBase64, true);
        if ($binario === false || $binario === '') {
            throw new \InvalidArgumentException('Conteúdo da imagem inválido.');
        }

        $tamanho = strlen($binario);
        if ($tamanho > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
        }

        // Confia no conteúdo real (não no mime informado pelo cliente).
        $mimeDetectado = $this->detectarMimeBinario($binario);
        if ($mimeDetectado === null || ! $this->mimePermitido($mimeDetectado)) {
            throw new \InvalidArgumentException('Formato de imagem não suportado.');
        }
        $mimeType = $mimeDetectado;

        $jaNotificavel = $this->osJaNotificavelNoChat($opTask);

        $anexo = OpTaskAnexo::create([
            'op_task_id' => $opTask->id,
            'nome_arquivo' => $this->normalizarNomeArquivo($nomeArquivo, $mimeType),
            'mime_type' => $mimeType,
            'tamanho_bytes' => $tamanho,
            'conteudo_base64' => $conteudoBase64,
            'enviado_por' => $enviadoPor,
            'criado_em' => now(),
        ]);

        if ($jaNotificavel) {
            $this->notificarNovoAnexoNoChat($opTask, $anexo);
        }

        return $anexo;
    }

    private function notificarNovoAnexoNoChat(OpTask $opTask, OpTaskAnexo $anexo): void
    {
        $opTaskId = (int) $opTask->id;
        $anexoId = (int) $anexo->id;

        app()->terminating(function () use ($opTaskId, $anexoId): void {
            $os = OpTask::find($opTaskId)?->fresh();
            $anexoAtual = OpTaskAnexo::find($anexoId);

            if (! $os || ! $anexoAtual) {
                return;
            }

            app(GoogleChatService::class)->enviarNovoAnexoOsNoChat($os, $anexoAtual);
        });
    }

    private function osJaNotificavelNoChat(OpTask $opTask): bool
    {
        if (($opTask->categoria ?? '') !== 'ordem-servico') {
            return false;
        }

        if (empty($opTask->parent_task_id)) {
            return false;
        }

        return in_array($opTask->status, ['Em andamento', 'Finalizada', 'Finalizar'], true);
    }

    /** @return array<int, array{url: string, nome_arquivo: string, mime_type: string}> */
    public function listarParaChat(OpTask $opTask): array
    {
        return $this->listar($opTask)
            ->map(fn (OpTaskAnexo $anexo) => [
                'url' => $this->gerarUrlPublicaChat($opTask, $anexo),
                'nome_arquivo' => $anexo->nome_arquivo,
                'mime_type' => $anexo->mime_type,
            ])
            ->all();
    }

    /**
     * Binários dos anexos para reenvio no Nicon (upload multipart).
     *
     * @return array<int, array{nome_arquivo: string, mime_type: string, conteudo: string}>
     */
    public function listarBinariosParaNicon(OpTask $opTask): array
    {
        $limite = max(1, (int) config('planner.anexos_chat.max_imagens_por_mensagem', 10));

        return $this->listar($opTask)
            ->take($limite)
            ->map(function (OpTaskAnexo $anexo) {
                $binario = base64_decode((string) $anexo->conteudo_base64, true);
                if ($binario === false || $binario === '') {
                    return null;
                }

                return [
                    'nome_arquivo' => (string) ($anexo->nome_arquivo ?: 'anexo.jpg'),
                    'mime_type' => (string) ($anexo->mime_type ?: 'image/jpeg'),
                    'conteudo' => $binario,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{nome_arquivo: string, mime_type: string, conteudo: string}|null
     */
    public function binarioParaNicon(OpTaskAnexo $anexo): ?array
    {
        $binario = base64_decode((string) $anexo->conteudo_base64, true);
        if ($binario === false || $binario === '') {
            return null;
        }

        return [
            'nome_arquivo' => (string) ($anexo->nome_arquivo ?: 'anexo.jpg'),
            'mime_type' => (string) ($anexo->mime_type ?: 'image/jpeg'),
            'conteudo' => $binario,
        ];
    }

    public function gerarUrlPublicaChat(OpTask $opTask, OpTaskAnexo $anexo, ?int $horasValidade = null): string
    {
        if ((int) $anexo->op_task_id !== (int) $opTask->id) {
            throw new \InvalidArgumentException('Anexo não pertence à OS.');
        }

        $horas = $horasValidade ?? (int) config('planner.anexos_chat.ttl_horas', 72);
        $horas = max(1, min($horas, 168));
        $expires = now()->addHours($horas)->getTimestamp();
        $token = $this->criarTokenChat((int) $opTask->id, (int) $anexo->id, $expires);
        $ext = $this->extensaoPublicaChat($anexo);

        return $this->urlBasePublicaChat().'/chat-img/'.$token.'.'.$ext;
    }

    public function responderPorTokenChat(string $token): Response
    {
        $dados = $this->resolverTokenChat($token);
        if ($dados === null) {
            abort(403, 'Link inválido ou expirado.');
        }

        $anexo = OpTaskAnexo::query()
            ->whereKey($dados['anexo_id'])
            ->where('op_task_id', $dados['op_task_id'])
            ->first();

        if (! $anexo) {
            abort(404, 'Anexo não encontrado.');
        }

        [$binario, $contentType] = $this->normalizarImagemParaGoogleChat($anexo);

        return response($binario, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($binario),
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function urlBasePublicaChat(): string
    {
        $base = rtrim((string) config('planner.anexos_chat.public_url', config('app.url')), '/');

        if ($base === '') {
            $base = 'http://localhost';
        }

        if (! str_contains($base, '://')) {
            $base = 'https://'.$base;
        }

        if (str_starts_with($base, 'http://') && ! app()->environment('local')) {
            $base = 'https://'.substr($base, 7);
        }

        return $base;
    }

    private function criarTokenChat(int $opTaskId, int $anexoId, int $expires): string
    {
        $payload = "{$expires}.{$opTaskId}.{$anexoId}";
        $assinatura = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 24);

        return $payload.'.'.$assinatura;
    }

    /** @return array{op_task_id: int, anexo_id: int}|null */
    public function resolverTokenChat(string $token): ?array
    {
        $token = preg_replace('/\.(jpe?g|png)$/i', '', trim($token)) ?? trim($token);

        if (! preg_match('/^(\d+)\.(\d+)\.(\d+)\.([a-f0-9]{24})$/', $token, $matches)) {
            return null;
        }

        $expires = (int) $matches[1];
        $opTaskId = (int) $matches[2];
        $anexoId = (int) $matches[3];
        $assinatura = $matches[4];

        if ($expires < time()) {
            return null;
        }

        $payload = "{$expires}.{$opTaskId}.{$anexoId}";
        $esperada = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 24);

        if (! hash_equals($esperada, $assinatura)) {
            return null;
        }

        return [
            'op_task_id' => $opTaskId,
            'anexo_id' => $anexoId,
        ];
    }

    private function extensaoPublicaChat(OpTaskAnexo $anexo): string
    {
        $mime = strtolower(trim((string) $anexo->mime_type));

        return $mime === 'image/png' ? 'png' : 'jpg';
    }

    public function excluir(OpTask $opTask, int $anexoId): void
    {
        $anexo = OpTaskAnexo::query()
            ->where('op_task_id', $opTask->id)
            ->where('id', $anexoId)
            ->first();

        if (! $anexo) {
            throw new \InvalidArgumentException('Anexo não encontrado.');
        }

        $anexo->delete();
    }

    public function responderArquivo(OpTask $opTask, int $anexoId): StreamedResponse
    {
        $anexo = $this->buscarAnexo($opTask, $anexoId);
        $binario = $this->decodificarBinario($anexo);

        return response()->stream(function () use ($binario) {
            echo $binario;
        }, 200, [
            'Content-Type' => $anexo->mime_type,
            'Content-Length' => (string) strlen($binario),
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.addslashes((string) $anexo->nome_arquivo).'"',
        ]);
    }

    /**
     * @deprecated rota legada — use /chat-img/{token}
     */
    public function responderArquivoPublicoChat(OpTask $opTask, int $anexoId): Response
    {
        $anexo = $this->buscarAnexo($opTask, $anexoId);
        $horas = (int) config('planner.anexos_chat.ttl_horas', 72);
        $token = $this->criarTokenChat(
            (int) $opTask->id,
            (int) $anexo->id,
            now()->addHours($horas)->getTimestamp(),
        );
        $ext = $this->extensaoPublicaChat($anexo);

        return $this->responderPorTokenChat($token.'.'.$ext);
    }

    private function buscarAnexo(OpTask $opTask, int $anexoId): OpTaskAnexo
    {
        $anexo = OpTaskAnexo::query()
            ->where('op_task_id', $opTask->id)
            ->where('id', $anexoId)
            ->first();

        if (! $anexo) {
            abort(404, 'Anexo não encontrado.');
        }

        return $anexo;
    }

    private function decodificarBinario(OpTaskAnexo $anexo): string
    {
        $binario = base64_decode($anexo->conteudo_base64, true);
        if ($binario === false) {
            abort(500, 'Não foi possível ler o anexo.');
        }

        return $binario;
    }

    /** @return array{0: string, 1: string} */
    private function normalizarImagemParaGoogleChat(OpTaskAnexo $anexo): array
    {
        $binario = $this->decodificarBinario($anexo);
        $mime = strtolower(trim((string) $anexo->mime_type));

        if (! extension_loaded('gd')) {
            return [$binario, in_array($mime, ['image/jpeg', 'image/png'], true) ? $mime : 'image/jpeg'];
        }

        $imagem = @imagecreatefromstring($binario);
        if ($imagem === false) {
            return [$binario, in_array($mime, ['image/jpeg', 'image/png'], true) ? $mime : 'image/jpeg'];
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($imagem);
        }
        imagealphablending($imagem, true);
        imagesavealpha($imagem, true);

        if ($mime === 'image/png') {
            ob_start();
            imagepng($imagem, null, 6);
            $png = ob_get_clean();
            imagedestroy($imagem);

            if ($png !== false && $png !== '') {
                return [$png, 'image/png'];
            }

            return [$binario, 'image/png'];
        }

        ob_start();
        imagejpeg($imagem, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($imagem);

        if ($jpeg === false || $jpeg === '') {
            return [$binario, 'image/jpeg'];
        }

        return [$jpeg, 'image/jpeg'];
    }

    private function mimePermitido(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true);
    }

    private function detectarMimeBinario(string $binario): ?string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_buffer($finfo, $binario) ?: null;
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $this->normalizarMimeType($mime);
                }
            }
        }

        $info = @getimagesizefromstring($binario);
        if (! is_array($info) || empty($info['mime'])) {
            return null;
        }

        return $this->normalizarMimeType((string) $info['mime']);
    }

    private function normalizarMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        return match ($mimeType) {
            'image/jpg' => 'image/jpeg',
            default => $mimeType,
        };
    }

    private function extrairBase64(string $valor): string
    {
        $valor = trim($valor);
        if (str_contains($valor, ',')) {
            return trim(substr($valor, strpos($valor, ',') + 1));
        }

        return $valor;
    }

    private function normalizarNomeArquivo(string $nome, string $mimeType): string
    {
        $nome = trim($nome) !== '' ? trim($nome) : 'anexo';
        $nome = basename(str_replace(['\\', "\0"], '', $nome));
        $nome = preg_replace('/[^\w.\- ()\[\]]+/u', '_', $nome) ?: 'anexo';

        $ext = match ($mimeType) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        // Força extensão compatível com o mime real detectado.
        $nome = preg_replace('/\.[^.]+$/', '', $nome) ?: 'anexo';

        return mb_substr($nome.'.'.$ext, 0, 255);
    }
}
