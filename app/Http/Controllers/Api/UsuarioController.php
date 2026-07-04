<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tecnico;
use App\Models\User;
use App\Support\UsuarioCargo;
use App\Services\UsuarioPermissaoService;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private static ?bool $temTabelaTecnicos = null;

    private const DEFAULT_ITERATIONS = 200000;

    private const REGIOES_TECNICO = [
        'Governador Valadares',
        'Vale do Aço',
    ];

    public function __construct(
        private UsuarioPermissaoService $permissoes,
    ) {}

    public function opcoes()
    {
        $tecnicosPorUsername = $this->tecnicosPorUsername();

        $usuarios = User::query()
            ->orderBy('username')
            ->get()
            ->map(function (User $user) use ($tecnicosPorUsername) {
                $tecnico = $tecnicosPorUsername->get($user->username);

                return [
                    'username' => $user->username,
                    'funcao' => $tecnico ? 'tecnico' : 'projetista',
                ];
            });

        return response()->json(['usuarios' => $usuarios], 200);
    }

    public function index()
    {
        $tecnicosPorUsername = $this->tecnicosPorUsername();

        $usuarios = User::query()
            ->select(['username', 'cargo', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $permissoesPorUsuario = $this->permissoes->listarPorUsuarios(
            $usuarios->pluck('username')->all()
        );

        $usuarios = $usuarios->map(function (User $user) use ($tecnicosPorUsername, $permissoesPorUsuario) {
                $tecnico = $tecnicosPorUsername->get($user->username);
                $ehTecnico = $tecnico !== null;

                return array_merge($this->formatarUsuario($user), [
                    'funcao' => $ehTecnico ? 'tecnico' : 'projetista',
                    'regiao' => $ehTecnico ? ($tecnico->regiao ?? '') : null,
                    'cargo' => $ehTecnico ? null : ($user->cargo ?? null),
                    'permissoes' => $permissoesPorUsuario[$user->username] ?? [],
                ]);
            });

        return response()->json([
            'usuarios' => $usuarios,
            'permissoes_disponiveis' => $this->permissoes->catalogo(),
            'cargos_disponiveis' => UsuarioCargo::catalogoFormatado(),
        ], 200);
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
            'password' => [Rule::requiredIf($request->input('funcao') === 'projetista'), 'nullable', 'string', 'min:8', 'max:200', 'confirmed'],
            'funcao' => ['required', Rule::in(['projetista', 'tecnico'])],
            'cargo' => [
                Rule::requiredIf($request->input('funcao') === 'projetista'),
                'nullable',
                'string',
                Rule::in(UsuarioCargo::chavesValidas()),
            ],
            'regiao' => [Rule::requiredIf($request->input('funcao') === 'tecnico'), 'nullable', 'string', Rule::in(self::REGIOES_TECNICO)],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['string', Rule::in($this->permissoes->chavesValidas())],
        ], [
            'username.required' => 'Informe o usuário.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou underline.',
            'username.unique' => 'Este usuário já existe.',
            'password.required' => 'Informe a senha.',
            'password.required_if' => 'Informe a senha para usuários projetistas.',
            'password.min' => 'A senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'funcao.required' => 'Selecione a função do usuário.',
            'funcao.in' => 'Selecione Projetista ou Técnico.',
            'cargo.required' => 'Selecione o cargo do usuário.',
            'cargo.in' => 'Selecione um cargo válido.',
            'regiao.required' => 'Selecione a região do técnico.',
            'regiao.in' => 'Selecione Governador Valadares ou Vale do Aço.',
        ]);

        if ($dados['funcao'] === 'tecnico') {
            $this->garantirTabelaTecnicos();
        }

        $usuario = DB::transaction(function () use ($dados) {
            $usuario = User::create([
                'username' => $dados['username'],
                'cargo' => $dados['funcao'] === 'projetista' ? ($dados['cargo'] ?? null) : null,
                ...$this->gerarSenha($dados['password'] ?? bin2hex(random_bytes(16))),
            ]);

            $this->sincronizarTecnico($usuario->username, $dados['funcao'], null, $dados['regiao'] ?? null);
            $this->permissoes->sincronizar($usuario->username, $dados['permissoes'] ?? []);

            return $usuario;
        });

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'usuario' => $this->formatarUsuarioComFuncao($usuario),
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
            'password' => ['nullable', 'string', 'min:8', 'max:200', 'confirmed'],
            'funcao' => ['required', Rule::in(['projetista', 'tecnico'])],
            'cargo' => [
                Rule::requiredIf($request->input('funcao') === 'projetista'),
                'nullable',
                'string',
                Rule::in(UsuarioCargo::chavesValidas()),
            ],
            'regiao' => [Rule::requiredIf($request->input('funcao') === 'tecnico'), 'nullable', 'string', Rule::in(self::REGIOES_TECNICO)],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['string', Rule::in($this->permissoes->chavesValidas())],
        ], [
            'username.required' => 'Informe o usuário.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou underline.',
            'username.unique' => 'Este usuário já existe.',
            'password.min' => 'A senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'funcao.required' => 'Selecione a função do usuário.',
            'funcao.in' => 'Selecione Projetista ou Técnico.',
            'cargo.required' => 'Selecione o cargo do usuário.',
            'cargo.in' => 'Selecione um cargo válido.',
            'regiao.required' => 'Selecione a região do técnico.',
            'regiao.in' => 'Selecione Governador Valadares ou Vale do Aço.',
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
            $user->cargo = $dados['funcao'] === 'projetista' ? ($dados['cargo'] ?? null) : null;

            if (filled($dados['password'] ?? null)) {
                $user->fill($this->gerarSenha($dados['password']));
            }

            $user->save();
            $this->sincronizarTecnico($user->username, $dados['funcao'], $usernameAnterior, $dados['regiao'] ?? null);

            if (array_key_exists('permissoes', $dados)) {
                $this->permissoes->sincronizar($user->username, $dados['permissoes']);
            }
        });

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'usuario' => $this->formatarUsuarioComFuncao($user->fresh()),
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
            if ($this->temTabelaTecnicos()) {
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
            'cargo' => $user->cargo,
            'created_at' => $user->created_at,
        ];
    }

    private function formatarUsuarioComFuncao(User $user): array
    {
        $tecnico = $this->temTabelaTecnicos()
            ? Tecnico::query()->where('username', $user->username)->first()
            : null;

        return array_merge($this->formatarUsuario($user), [
            'funcao' => $tecnico ? 'tecnico' : 'projetista',
            'regiao' => $tecnico?->regiao,
            'cargo' => $tecnico ? null : ($user->cargo ?? null),
            'permissoes' => $this->permissoes->listarPorUsuario($user->username),
        ]);
    }

    /** @return \Illuminate\Support\Collection<string, Tecnico> */
    private function tecnicosPorUsername()
    {
        if (! $this->temTabelaTecnicos()) {
            return collect();
        }

        return Tecnico::query()
            ->select(['username', 'regiao'])
            ->whereNotNull('username')
            ->get()
            ->keyBy('username');
    }

    private function temTabelaTecnicos(): bool
    {
        if (self::$temTabelaTecnicos === null) {
            self::$temTabelaTecnicos = Schema::hasTable('tecnicos');
        }

        return self::$temTabelaTecnicos;
    }

    private function sincronizarTecnico(
        string $username,
        string $funcao,
        ?string $usernameAnterior = null,
        ?string $regiao = null,
    ): void {
        if (! $this->temTabelaTecnicos()) {
            return;
        }

        $usernameAnterior ??= $username;

        if ($funcao === 'tecnico') {
            Tecnico::updateOrCreate(
                ['username' => $usernameAnterior],
                [
                    'username' => $username,
                    'nome' => $username,
                    'regiao' => $regiao ?? '',
                ]
            );
            return;
        }

        Tecnico::where('username', $usernameAnterior)->delete();
    }

    private function garantirTabelaTecnicos(): void
    {
        if ($this->temTabelaTecnicos()) {
            return;
        }

        self::$temTabelaTecnicos = null;

        Schema::create('tecnicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('username', 120)->nullable()->unique();
            $table->string('regiao', 64)->default('');
            $table->timestamps();

            $table->index('nome');
            $table->index('regiao');
        });

        self::$temTabelaTecnicos = true;
    }
}
