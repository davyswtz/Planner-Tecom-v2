<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UsuarioCargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:120',
            'password' => 'required|string|max:200',
        ]);

        $username = trim((string) $request->input('username'));
        $chave = 'login:'.sha1(strtolower($username).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            $segundos = RateLimiter::availableIn($chave);

            return response()->json([
                'message' => 'Muitas tentativas de login. Tente novamente em '.$segundos.' segundos.',
            ], 429);
        }

        $user = User::where('username', $username)->first();
        $credenciaisValidas = false;

        if ($user && is_string($user->pass_salt) && is_string($user->pass_hash)) {
            $salt = @hex2bin($user->pass_salt);
            $expected = @hex2bin($user->pass_hash);
            if ($salt !== false && $expected !== false) {
                $computed = hash_pbkdf2(
                    'sha256',
                    (string) $request->input('password'),
                    $salt,
                    max(1, (int) $user->pass_iterations),
                    32,
                    true
                );
                $credenciaisValidas = hash_equals($expected, $computed);
            }
        }

        if (! $credenciaisValidas) {
            RateLimiter::hit($chave, 60);

            // Mensagem única evita enumeração de usuários.
            return response()->json(['message' => 'Usuário ou senha incorretos.'], 401);
        }

        RateLimiter::clear($chave);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UsuarioCargo::dadosSessao($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => UsuarioCargo::dadosSessao($request->user()),
        ]);
    }
}