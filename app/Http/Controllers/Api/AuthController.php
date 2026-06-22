<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Tecnico;
use App\Http\Controllers\Controller;
use App\Services\UsuarioPermissaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function __construct(
        private UsuarioPermissaoService $permissoes,
    ) {}

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

        $ehTecnico = Schema::hasTable('tecnicos')
            && Tecnico::query()->where('username', $user->username)->exists();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->username,
                'username' => $user->username,
                'funcao' => $ehTecnico ? 'tecnico' : 'projetista',
                'permissoes' => $this->permissoes->listarPorUsuario($user->username),
            ],
        ]);

    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $ehTecnico = Schema::hasTable('tecnicos')
            && Tecnico::query()->where('username', $user->username)->exists();

        return response()->json([
            'user' => [
                'id' => $user->username,
                'username' => $user->username,
                'funcao' => $ehTecnico ? 'tecnico' : 'projetista',
                'permissoes' => $this->permissoes->listarPorUsuario($user->username),
            ],
        ]);
    }

}



    
    
    

