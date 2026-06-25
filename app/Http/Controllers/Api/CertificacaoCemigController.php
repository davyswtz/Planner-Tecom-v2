<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpTask;
use App\Services\CertificacaoCemigService;
use App\Services\OpTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificacaoCemigController extends Controller
{
    public function __construct(
        private CertificacaoCemigService $certificacaoService,
        private OpTaskService $opTaskService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $resultado = $this->certificacaoService->getCertificacoes(
            (int) $request->query('limit', 10),
            (int) $request->query('offset', 0),
            $request->query('status'),
            $request->query('regiao'),
            $request->query('tecnico'),
            $request->query('taskCode'),
            $request->query('dataInicio'),
            $request->query('dataFim'),
            $request->query('busca'),
        );

        return response()->json([
            'message' => 'Certificações listadas com sucesso',
            'certificacaoCemig' => $resultado,
        ]);
    }

    public function show(OpTask $certificacao_cemig): JsonResponse
    {
        if (! $certificacao_cemig->isCertificacaoCemigPai()) {
            return response()->json(['message' => 'Certificação não encontrada'], 404);
        }

        return response()->json([
            'message' => 'Certificação encontrada com sucesso',
            'certificacaoCemig' => $this->certificacaoService->normalizarParaExibicao($certificacao_cemig),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'regiao' => ['required', 'string', 'max:120'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'prazo' => ['nullable', 'date'],
            'prioridade' => ['nullable', 'string', 'in:Baixa,Média,Alta'],
            'descricao' => ['nullable', 'string'],
            'protocolo' => ['nullable', 'string', 'max:120'],
        ], [
            'titulo.required' => 'Informe o título ou notificação.',
            'regiao.required' => 'Selecione a região.',
        ]);

        $certificacao = $this->certificacaoService->createCertificacao($request->all());

        return response()->json([
            'message' => 'Certificação criada com sucesso',
            'certificacaoCemig' => $this->certificacaoService->normalizarParaExibicao($certificacao),
        ], 201);
    }

    public function update(Request $request, OpTask $certificacao_cemig): JsonResponse
    {
        if (! $certificacao_cemig->isCertificacaoCemigPai()) {
            return response()->json(['message' => 'Certificação não encontrada'], 404);
        }

        $resultado = $this->certificacaoService->updateCertificacao($certificacao_cemig, $request->all());

        return response()->json([
            'message' => 'Certificação atualizada com sucesso',
            'certificacaoCemig' => $this->certificacaoService->normalizarParaExibicao($resultado),
        ]);
    }

    public function destroy(OpTask $certificacao_cemig): JsonResponse
    {
        if (! $certificacao_cemig->isCertificacaoCemigPai()) {
            return response()->json(['message' => 'Certificação não encontrada'], 404);
        }

        $this->certificacaoService->deleteCertificacao($certificacao_cemig);

        return response()->json(['message' => 'Certificação excluída com sucesso']);
    }

    public function listarOS(int $id): JsonResponse
    {
        $pai = OpTask::find($id);

        if (! $pai?->isCertificacaoCemigPai()) {
            return response()->json(['message' => 'Certificação não encontrada'], 404);
        }

        $os = $this->opTaskService->listarOsVinculadas($id);

        return response()->json(['os' => $os]);
    }
}
