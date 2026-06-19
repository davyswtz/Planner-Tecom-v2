<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private const DEFAULT_ITERATIONS = 200000;

    public function index()
    {
        $tecnicosPorUsername = Schema::hasTable('tecnicos')
            ? Tecnico::query()->whereNotNull('username')->pluck('username')->all()
            : [];

        $usuarios = User::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $user) => array_merge($this->formatarUsuario($user), [
                'funcao' => in_array($user->username, $tecnicosPorUsername, true) ? 'tecnico' : 'projetista',
            ]));

        return response()->json(['usuarios' => $usuarios], 200);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'username' => [
                'required',
                'string',
                'max:120',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('usuario', 'username'),
            ],
            'password' => [Rule::requiredIf($request->input('funcao') === 'projetista'), 'nullable', 'string', 'min:4', 'confirmed'],
            'funcao' => ['required', Rule::in(['projetista', 'tecnico'])],
        ], [
            'username.required' => 'Informe o usuário.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou underline.',
            'username.unique' => 'Este usuário já existe.',
            'password.required' => 'Informe a senha.',
            'password.required_if' => 'Informe a senha para usuários projetistas.',
            'password.min' => 'A senha precisa ter pelo menos 4 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'funcao.required' => 'Selecione a função do usuário.',
            'funcao.in' => 'Selecione Projetista ou Técnico.',
        ]);

        if ($dados['funcao'] === 'tecnico') {
            $this->garantirTabelaTecnicos();
        }

        $usuario = DB::transaction(function () use ($dados) {
            $usuario = User::create([
                'username' => $dados['username'],
                ...$this->gerarSenha($dados['password'] ?? bin2hex(random_bytes(16))),
            ]);

            $this->sincronizarTecnico($usuario->username, $dados['funcao']);

            return $usuario;
        });

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'usuario' => $this->formatarUsuario($usuario),
        ], 201);
    }

    public function update(Request $request, string $usuario)
    {
        $user = User::where('username', $usuario)->first();

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        $dados = $request->validate([
            'username' => [
                'required',
                'string',
                'max:120',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('usuario', 'username')->ignore($usuario, 'username'),
            ],
            'password' => ['nullable', 'string', 'min:4', 'confirmed'],
            'funcao' => ['required', Rule::in(['projetista', 'tecnico'])],
        ], [
            'username.required' => 'Informe o usuário.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou underline.',
            'username.unique' => 'Este usuário já existe.',
            'password.min' => 'A senha precisa ter pelo menos 4 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'funcao.required' => 'Selecione a função do usuário.',
            'funcao.in' => 'Selecione Projetista ou Técnico.',
        ]);

        $usuarioLogado = $request->user()?->username;
        if ($usuarioLogado === $usuario && $dados['username'] !== $usuario) {
            return response()->json(['message' => 'Você não pode alterar o nome do próprio usuário logado.'], 422);
        }

        if ($dados['funcao'] === 'tecnico') {
            $this->garantirTabelaTecnicos();
        }

        DB::transaction(function () use ($user, $usuario, $dados) {
            $usernameAnterior = $usuario;
            $user->username = $dados['username'];

            if (filled($dados['password'] ?? null)) {
                $user->fill($this->gerarSenha($dados['password']));
            }

            $user->save();
            $this->sincronizarTecnico($user->username, $dados['funcao'], $usernameAnterior);
        });

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'usuario' => $this->formatarUsuario($user->fresh()),
        ], 200);
    }

    public function destroy(Request $request, string $usuario)
    {
        $user = User::where('username', $usuario)->first();

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        if ($request->user()?->username === $usuario) {
            return response()->json(['message' => 'Você não pode excluir o próprio usuário logado.'], 422);
        }

        if (User::count() <= 1) {
            return response()->json(['message' => 'Não é possível excluir o último usuário do sistema.'], 422);
        }

        $user->tokens()->delete();
        DB::transaction(function () use ($user, $usuario) {
            if (Schema::hasTable('tecnicos')) {
                Tecnico::where('username', $usuario)->delete();
            }

            $user->delete();
        });

        return response()->json(['message' => 'Usuário excluído com sucesso'], 200);
    }

    private function gerarSenha(string $senha): array
    {
        $salt = random_bytes(32);
        $hash = hash_pbkdf2(
            'sha256',
            $senha,
            $salt,
            self::DEFAULT_ITERATIONS,
            32,
            true
        );

        return [
            'pass_salt' => bin2hex($salt),
            'pass_hash' => bin2hex($hash),
            'pass_iterations' => self::DEFAULT_ITERATIONS,
        ];
    }

    private function formatarUsuario(User $user): array
    {
        return [
            'username' => $user->username,
            'created_at' => $user->created_at,
        ];
    }

    private function sincronizarTecnico(string $username, string $funcao, ?string $usernameAnterior = null): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        $usernameAnterior ??= $username;

        if ($funcao === 'tecnico') {
            Tecnico::updateOrCreate(
                ['username' => $usernameAnterior],
                [
                    'username' => $username,
                    'nome' => $username,
                ]
            );
            return;
        }

        Tecnico::where('username', $usernameAnterior)->delete();
    }

    private function garantirTabelaTecnicos(): void
    {
        if (Schema::hasTable('tecnicos')) {
            return;
        }

        Schema::create('tecnicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('username', 120)->nullable()->unique();
            $table->string('regiao', 64)->default('');
            $table->timestamps();

            $table->index('nome');
            $table->index('regiao');
        });
    }
}
