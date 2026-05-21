<?php

namespace App\Http\Controllers;

use App\Services\RompimentoService;
use Illuminate\Http\Request;

class RompimentoController
{
    private RompimentoService $rompimentoService;

    public function __construct(RompimentoService $rompimentoService)
    {
        $this->rompimentoService = $rompimentoService;
    }

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