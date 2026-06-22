<?php

namespace App\Http\Middleware;

use App\Services\UsuarioPermissaoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioPermissao
{
    public function __construct(private UsuarioPermissaoService $permissoes)
    {
    }

    public function handle(Request $request, Closure $next, string $permissao): Response
    {
        $username = $request->user()?->username;

        if (! $username || ! $this->permissoes->possui($username, $permissao)) {
            return response()->json([
                'message' => 'Sem permissão para esta ação.',
            ], 403);
        }

        return $next($request);
    }
}
