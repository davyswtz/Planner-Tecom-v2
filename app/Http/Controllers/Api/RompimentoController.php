<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RompimentoService;
use App\Services\OpTaskService;
use App\Models\OpTask;
use Illuminate\Http\Request;

class RompimentoController extends Controller
{
    public function __construct(private RompimentoService $rompimentoService)
    {}

    public function index()
    {
        $resultado = $this->rompimentoService->getRompimentos();
        return response()->json(['message' => 'Rompimentos listados com sucesso', 'rompimentos' => $resultado], 200);
    }
 
    public function show(OpTask $rompimento){
        if($rompimento->categoria !== 'rompimentos'){
            return response()->json(['message' => 'Rompimento não encontrado'], 404);
        }else{
            return response()->json(['message' => 'Rompimento encontrado com sucesso', 'rompimento' => $rompimento], 200);
        }
    }

    public function store(Request $request)
    {
       $rompimento = $this->rompimentoService->createRompimento($request->all());
       return response()->json(['message' => 'Rompimento criado com sucesso', 'rompimento' => $rompimento], 201);
    }

    public function update(Request $request, OpTask $rompimento){
        $resultado = $this->rompimentoService->updateRompimento($rompimento, $request->all());
        return response()->json(['message' => 'Rompimento atualizado com sucesso', 'rompimento' => $resultado], 200);
    }

    public function destroy(OpTask $rompimento){
        $resultado = $this->rompimentoService->deleteRompimento($rompimento);
        return response()->json(['message' => 'Rompimento deletado com sucesso'], 200);
    }
}
