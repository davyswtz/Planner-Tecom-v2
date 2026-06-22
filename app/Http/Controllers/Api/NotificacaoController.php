<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    public function __construct(private NotificacaoService $notificacoes)
    {
    }

    public function index(Request $request)
    {
        $username = $request->user()?->username ?? '';

        return response()->json([
            'notificacoes' => $this->notificacoes->listarPorUsuario($username),
            'nao_lidas' => $this->notificacoes->contarNaoLidas($username),
        ]);
    }

    public function marcarLida(Request $request, int $id)
    {
        $username = $request->user()?->username ?? '';
        $ok = $this->notificacoes->marcarComoLida($id, $username);

        if (! $ok) {
            return response()->json(['message' => 'Notificação não encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Notificação marcada como lida.',
            'nao_lidas' => $this->notificacoes->contarNaoLidas($username),
        ]);
    }

    public function marcarTodasLidas(Request $request)
    {
        $username = $request->user()?->username ?? '';
        $this->notificacoes->marcarTodasComoLidas($username);

        return response()->json([
            'message' => 'Todas as notificações foram marcadas como lidas.',
            'nao_lidas' => 0,
        ]);
    }
}
