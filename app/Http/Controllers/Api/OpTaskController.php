<?php

namespace App\Http\Controllers\Api;


use App\Services\NotificacaoService;
use App\Services\OpTaskService;
use App\Services\UsuarioPermissaoService;
use App\Http\Controllers\Controller;
use App\Models\OpTask;
use Illuminate\Http\Request;

class OpTaskController extends Controller
{
    public function __construct(
        private OpTaskService $opTaskService,
        private UsuarioPermissaoService $permissoes,
        private NotificacaoService $notificacoes,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = min(500, max(1, (int) $request->query('limit', 200)));
        $categoria = $request->query('categoria');
        $categoria = is_string($categoria) && $categoria !== '' ? $categoria : null;

        $username = $request->user()?->username;
        $responsavel = null;
        $excluirFinalizadas = false;

        if ($request->boolean('minhas')) {
            if (! $username) {
                return response()->json([]);
            }

            $categoria = 'tarefas';
            $responsavel = $username;
            $excluirFinalizadas = true;
        } elseif ($categoria === 'tarefas') {
            if (! $this->usuarioPodeAcessarAbaTarefas($request)) {
                return response()->json(['message' => 'Sem permissão para visualizar a aba de tarefas.'], 403);
            }
        }

        $resultado = $this->opTaskService->getOpTasks($limit, 'updated_at', 'desc', $categoria, $responsavel, $excluirFinalizadas);

        return response()->json($resultado);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:64'],
            'descricao' => ['nullable', 'string'],
            'responsavel' => ['nullable', 'string', 'max:120'],
            'prazo' => ['nullable', 'date'],
            'prioridade' => ['nullable', 'string', 'in:Baixa,Média,Alta'],
            'status' => ['nullable', 'string', 'max:64'],
        ], [
            'titulo.required' => 'Informe o título da tarefa.',
            'categoria.required' => 'Informe a categoria da tarefa.',
            'prazo.date' => 'Informe uma data de prazo válida.',
        ]);

        $dados = $request->only((new OpTask)->getFillable());

        if (($dados['categoria'] ?? '') === 'tarefas' && ! $this->usuarioPodeAcessarAbaTarefas($request)) {
            return response()->json(['message' => 'Sem permissão para criar tarefas.'], 403);
        }

        if (($dados['categoria'] ?? '') === 'tarefas') {
            $opTask = $this->opTaskService->createTarefa($dados);
            $this->notificacoes->notificarTarefaAtribuida(
                $opTask,
                $request->user()?->username ?? ''
            );
        } else {
            $opTask = $this->opTaskService->createOpTask($dados);
        }

        return response()->json($opTask->fresh(), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, OpTask $opTask)
    {
        if ($opTask->categoria === 'tarefas' && ! $this->usuarioPodeInteragirComTarefa($request, $opTask)) {
            return response()->json(['message' => 'Sem permissão para visualizar esta tarefa.'], 403);
        }

        $resultado = $this->opTaskService->showOpTask($opTask);
        return response()->json($resultado);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OpTask $opTask)
    {
        
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OpTask $opTask)
    {
        if ($opTask->categoria === 'tarefas' && ! $this->usuarioPodeInteragirComTarefa($request, $opTask)) {
            return response()->json(['message' => 'Sem permissão para alterar esta tarefa.'], 403);
        }

        try {
            if ($opTask->categoria === 'tarefas') {
                $request->validate([
                    'titulo' => ['sometimes', 'required', 'string', 'max:255'],
                    'descricao' => ['nullable', 'string'],
                    'responsavel' => ['nullable', 'string', 'max:120'],
                    'prazo' => ['nullable', 'date'],
                    'prioridade' => ['nullable', 'string', 'in:Baixa,Média,Alta'],
                    'status' => ['nullable', 'string', 'max:64'],
                ], [
                    'titulo.required' => 'Informe o título da tarefa.',
                    'prazo.date' => 'Informe uma data de prazo válida.',
                ]);

                $dados = $request->only(['titulo', 'descricao', 'responsavel', 'prazo', 'prioridade', 'status']);
                $opTask = $this->opTaskService->updateTarefa($opTask, $dados);
            } else {
                $opTask = $this->opTaskService->updateOpTask($opTask, $request->all());
            }

            return response()->json(['message' => 'OpTask atualizado com sucesso', 'opTask' => $opTask]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Exclui uma Ordem de Serviço (OpTask).
     *
     * Rota: DELETE /api/op-tasks/{opTask}
     * Usado pelas telas de Rompimento e Troca de Poste na aba "Ordens de Serviço".
     */
    public function destroy(Request $request, OpTask $opTask)
    {
        if ($opTask->categoria === 'tarefas' && ! $this->usuarioPodeInteragirComTarefa($request, $opTask)) {
            return response()->json(['message' => 'Sem permissão para excluir esta tarefa.'], 403);
        }

        try {
            if ($opTask->categoria === 'tarefas') {
                $this->opTaskService->deleteTarefa($opTask);

                return response()->json(['message' => 'Tarefa excluída com sucesso'], 200);
            }

            $this->opTaskService->deleteOpTask($opTask);

            return response()->json(['message' => 'Ordem de serviço excluída com sucesso'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function usuarioPodeAcessarAbaTarefas(Request $request): bool
    {
        $username = $request->user()?->username;

        return $username && $this->permissoes->possui($username, 'visualizar_aba_tarefas');
    }

    private function usuarioPodeInteragirComTarefa(Request $request, OpTask $opTask): bool
    {
        $username = $request->user()?->username;

        if (! $username) {
            return false;
        }

        if ($this->permissoes->possui($username, 'visualizar_aba_tarefas')) {
            return true;
        }

        return $opTask->responsavel === $username;
    }
}
