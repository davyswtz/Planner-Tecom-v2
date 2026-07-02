<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\OpTaskAnexoService;
use Illuminate\Http\Request;

class OpTaskAnexoController extends Controller
{
    public function __construct(private OpTaskAnexoService $anexos)
    {
    }

    public function index(OpTask $opTask)
    {
        return response()->json([
            'anexos' => $this->anexos->listarFormatado($opTask),
        ]);
    }

    public function store(Request $request, OpTask $opTask)
    {
        $dados = $request->validate([
            'nome_arquivo' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:100'],
            'conteudo_base64' => ['required', 'string'],
        ]);

        try {
            $anexo = $this->anexos->salvar(
                $opTask,
                $dados['nome_arquivo'],
                $dados['mime_type'],
                $dados['conteudo_base64'],
                $request->user()?->username,
            );

            return response()->json([
                'message' => 'Anexo salvo com sucesso.',
                'anexo' => [
                    'id' => $anexo->id,
                    'nome_arquivo' => $anexo->nome_arquivo,
                    'mime_type' => $anexo->mime_type,
                    'tamanho_bytes' => $anexo->tamanho_bytes,
                    'enviado_por' => $anexo->enviado_por,
                    'criado_em' => optional($anexo->criado_em)->toIso8601String(),
                    'url' => url("/api/op-tasks/{$opTask->id}/anexos/{$anexo->id}/arquivo"),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(OpTask $opTask, int $anexo)
    {
        try {
            $this->anexos->excluir($opTask, $anexo);

            return response()->json(['message' => 'Anexo removido com sucesso.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function arquivo(OpTask $opTask, int $anexo)
    {
        return $this->anexos->responderArquivo($opTask, $anexo);
    }

    public function imagemChat(string $token)
    {
        return $this->anexos->responderPorTokenChat($token);
    }
}
