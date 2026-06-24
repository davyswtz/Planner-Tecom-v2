<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeoGrid\GeoGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GeoGridController extends Controller
{
    public function __construct(private GeoGridService $geoGrid)
    {
    }

    public function buscarCaixa(Request $request): JsonResponse
    {
        $request->validate([
            'termo' => ['nullable', 'string', 'max:120'],
            'caixa' => ['nullable', 'string', 'max:120'],
            'sigla' => ['nullable', 'string', 'max:120'],
            'id_geogrid' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($request->filled('id_geogrid')) {
            try {
                $caixa = $this->geoGrid->buscarCaixaPorId((int) $request->input('id_geogrid'));

                if ($caixa === null) {
                    return response()->json([
                        'message' => 'Caixa não encontrada no GeoGrid.',
                        'caixa' => null,
                    ], 404);
                }

                return response()->json([
                    'message' => 'Caixa localizada no GeoGrid.',
                    'caixa' => $caixa,
                ]);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 502);
            }
        }

        $termo = trim((string) ($request->input('termo')
            ?: $request->input('caixa')
            ?: $request->input('sigla')
            ?: ''));

        if ($termo === '') {
            return response()->json([
                'message' => 'Informe o termo, caixa ou sigla para buscar no GeoGrid.',
            ], 422);
        }

        try {
            $caixa = $this->geoGrid->buscarCaixaPorTermo($termo);

            if ($caixa === null) {
                return response()->json([
                    'message' => "Caixa \"{$termo}\" não encontrada no GeoGrid.",
                    'caixa' => null,
                ], 404);
            }

            return response()->json([
                'message' => 'Caixa localizada no GeoGrid.',
                'caixa' => $caixa,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function listarCaixas(): JsonResponse
    {
        try {
            $resultado = $this->geoGrid->listarCaixasGovernadorValadares();

            return response()->json([
                'message' => 'Caixas de Governador Valadares carregadas.',
                'caixas' => $resultado['caixas'],
                'total_ids' => $resultado['total_ids'],
                'total_com_coordenadas' => $resultado['total_com_coordenadas'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function mapaItens(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $registros = $this->geoGrid->obterMapaItens($request->input('ids'));

            return response()->json([
                'message' => 'Dados de mapa obtidos com sucesso.',
                'registros' => $registros,
                'total' => count($registros),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
