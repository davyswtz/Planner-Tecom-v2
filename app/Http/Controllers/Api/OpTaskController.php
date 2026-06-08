<?php

namespace App\Http\Controllers\Api;


use App\Services\GoogleChatService;
use App\Services\OpTaskService;
use App\Http\Controllers\Controller;
use App\Models\OpTask;
use Illuminate\Http\Request;

class OpTaskController extends Controller
{
    public function __construct(private OpTaskService $opTaskService)
    {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultado = $this->opTaskService->getOpTasks();
        return response()->json($resultado);     }

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
        $opTask = $this->opTaskService->createOpTask($request->all());
        return response()->json($opTask);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(OpTask $opTask)
    {
        $resultado = $this->opTaskService->showOpTask($opTask);
        return response()->json($resultado);
        
        //
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
       $opTask = $this->opTaskService->updateOpTask($opTask, $request->all());
       return response()->json(['message' => 'OpTask atualizado com sucesso', 'opTask' => $opTask]);
        //
    }

    /**
     * Exclui uma Ordem de Serviço (OpTask).
     *
     * Rota: DELETE /api/op-tasks/{opTask}
     * Usado pelas telas de Rompimento e Troca de Poste na aba "Ordens de Serviço".
     */
    public function destroy(OpTask $opTask)
    {
        try {
            $this->opTaskService->deleteOpTask($opTask);

            return response()->json(['message' => 'Ordem de serviço excluída com sucesso'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
