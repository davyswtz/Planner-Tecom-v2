<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookConfigController extends Controller
{
    public function __construct(private WebhookService $webhooks)
    {
    }

    public function show(Request $request)
    {
        return response()->json([
            'webhooks' => $this->webhooks->listarParaConfiguracao(),
            'webhookConfig' => $this->webhooks->montarConfigLegacy(),
        ]);
    }

    public function testar(Request $request, int $id)
    {
        $resultado = $this->webhooks->enviarTeste($id, $request->user()?->username ?? '');

        return response()->json($resultado, $resultado['ok'] ? 200 : 422);
    }

    public function update(Request $request)
    {
        $request->validate([
            'webhookConfig' => ['required', 'array'],
        ]);

        $this->webhooks->salvarDeConfigLegacy($request->input('webhookConfig'));

        return response()->json([
            'message' => 'Configuração de webhook salva com sucesso.',
            'webhooks' => $this->webhooks->listarFormatado(),
            'webhookConfig' => $this->webhooks->montarConfigLegacy(),
        ]);
    }
}
