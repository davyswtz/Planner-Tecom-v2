<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RompimentoService;
use Illuminate\Http\Request;

class RompimentoController extends Controller
{
    public function __construct(private RompimentoService $rompimentoService)
    {}

    public function index()
    {
        $resultado = $this->rompimentoService->getRompimentos();
        return response()->json($resultado);
    }

    public function store(Request $request)
    {
        $rompimento = $this->rompimentoService->createRompimento($request->all());
        return response()->json($rompimento);
    }
}
