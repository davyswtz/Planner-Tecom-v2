<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UsuarioCargo;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request){
        $request->validate([
            'username' => 'required|string',
            'password'=> 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();
    
        if(!$user){
            return response()->json(['message' => 'Usuario não encontrado'], 401);
        }

        $salt     = hex2bin($user->pass_salt);
        $expected = hex2bin($user->pass_hash);
        $computed = hash_pbkdf2('sha256', $request->password, $salt, $user->pass_iterations, 32, true);
        
        if (!hash_equals($expected, $computed)) {
            return response()->json(['message' => 'Senha incorreta'], 401);
        }

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