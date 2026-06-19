<?php

namespace App\Http\Controllers\Api;

use App\Services\TecnicoService;
use App\Models\OsTecnico;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function __construct(private TecnicoService $tecnicoService){}

    public function index(Request $request)
    {
        $resultado = $this->tecnicoService->getTecnicos($request->query('regiao'));
        return response()->json($resultado, 200);
    }

    public function show(OsTecnico $tecnico)
    {
        $resultado = $this->tecnicoService->showTecnico($tecnico);

        return response()->json(['message' => 'Tecnico encontrado com sucesso', 'tecnico' => $resultado], 200);
    }
}
