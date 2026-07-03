<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrdemServicoService;
use Illuminate\Http\Request;

class OrdemServicoController extends Controller
{
    public function __construct(
        private OrdemServicoService $ordemServicoService,
    ) {}

    public function dashboard(Request $request)
    {
        $filtros = $this->extrairFiltros($request);

        return response()->json(
            $this->ordemServicoService->getDashboard($filtros),
            200,
        );
    }

    public function index(Request $request)
    {
        $filtros = $this->extrairFiltros($request);
        $limit = max(1, min(200, (int) $request->query("limit", 50)));
        $offset = max(0, (int) $request->query("offset", 0));

        return response()->json(
            $this->ordemServicoService->listar($filtros, $limit, $offset),
            200,
        );
    }

    public function show(int $id)
    {
        $os = $this->ordemServicoService->show($id);

        if (!$os) {
            return response()->json(
                ["message" => "Ordem de serviço não encontrada"],
                404,
            );
        }

        return response()->json(["os" => $os], 200);
    }

    public function exportar(Request $request)
    {
        $filtros = $this->extrairFiltros($request);

        return $this->ordemServicoService->exportarPlanilha($filtros);
    }

    public function heatmap(Request $request)
    {
        $tipo = $request->query("tipo", "mensal");
        $mes = $request->query("mes");

        return response()->json(
            $this->ordemServicoService->getHeatmap($tipo, $mes),
            200,
        );
    }

    private function extrairFiltros(Request $request): array
    {
        $filtros = array_filter(
            [
                "regiao" => $request->query("regiao"),
                "tecnico" => $request->query("tecnico"),
                "status" => $request->query("status"),
                "prioridade" => $request->query("prioridade"),
                "categoriaPai" => $request->query("categoriaPai"),
                "dataInicio" => $request->query("dataInicio"),
                "dataFim" => $request->query("dataFim"),
                "tipoData" => $request->query("tipoData"),
                "busca" => $request->query("busca"),
            ],
            fn($valor) => $valor !== null && trim((string) $valor) !== "",
        );

        if (empty($filtros["dataInicio"]) && empty($filtros["dataFim"])) {
            unset($filtros["tipoData"]);
        }

        return $filtros;
    }
}
