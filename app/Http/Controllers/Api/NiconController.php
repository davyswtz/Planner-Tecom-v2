<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Nicon\NiconWebService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NiconController extends Controller
{
    public function __construct(private NiconWebService $niconWeb)
    {
    }

    public function buscarSinalCaixa(Request $request): JsonResponse
    {
        $request->validate([
            'id_cidade' => ['nullable', 'integer', 'min:1'],
            'id_caixa_optica' => ['nullable', 'integer', 'min:1'],
            'caixa' => ['nullable', 'string', 'max:120'],
            'clientes_servicos' => ['nullable', 'array', 'min:1'],
            'clientes_servicos.*' => ['required'],
            'clientesServicos' => ['nullable', 'array', 'min:1'],
            'clientesServicos.*' => ['required'],
            'clientes' => ['nullable', 'array', 'min:1'],
            'clientes.*.id_cliente_servico' => ['required'],
            'clientes.*.serial' => ['nullable', 'string', 'max:64'],
            'completar_sinais' => ['nullable', 'boolean'],
        ]);

        $temCidadeCaixa = $request->filled('id_cidade') && $request->filled('caixa');
        $ids = $request->input('clientes_servicos')
            ?? $request->input('clientesServicos')
            ?? [];
        $seriaisPorId = [];

        if ($request->filled('clientes') && is_array($request->input('clientes'))) {
            $ids = [];
            foreach ($request->input('clientes') as $cliente) {
                if (! is_array($cliente)) {
                    continue;
                }
                $id = (int) ($cliente['id_cliente_servico'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $ids[] = $id;
                $serial = trim((string) ($cliente['serial'] ?? ''));
                if ($serial !== '') {
                    $seriaisPorId[$id] = $serial;
                }
            }
        }

        if (! $temCidadeCaixa && $ids === []) {
            return response()->json([
                'message' => 'Informe id_cidade e caixa, ou a lista clientes_servicos.',
            ], 422);
        }

        try {
            if ($temCidadeCaixa) {
                $idCidade = (int) $request->input('id_cidade');
                $nomeCaixa = $request->string('caixa')->toString();
                $idCaixaOptica = $request->filled('id_caixa_optica')
                    ? (int) $request->input('id_caixa_optica')
                    : null;

                $clientes = $request->boolean('completar_sinais', true)
                    ? $this->niconWeb->buscarSinaisPorCidadeECaixa($idCidade, $nomeCaixa, $idCaixaOptica)
                    : $this->niconWeb->enriquecerComStatusConexao(
                        $this->niconWeb->listarClientesPorCaixa($idCidade, $nomeCaixa, $idCaixaOptica)
                    );

                return response()->json([
                    'message' => 'Consulta por cidade e caixa realizada com sucesso.',
                    'clientes' => $clientes,
                ]);
            }

            $clientes = $request->boolean('completar_sinais', true)
                ? $this->niconWeb->buscarSinaisCompletos($ids, $seriaisPorId)
                : $this->niconWeb->buscarSinalClientes($ids);

            return response()->json([
                'message' => 'Sinais dos clientes obtidos com sucesso.',
                'clientes' => $clientes,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function buscarSinalAtualCliente(Request $request): JsonResponse
    {
        $request->validate([
            'id_cliente_servico' => ['required', 'integer', 'min:1'],
            'serial' => ['nullable', 'string', 'max:64'],
            'forcar_refresh_tr069' => ['nullable', 'boolean'],
        ]);

        try {
            $sinal = $this->niconWeb->buscarSinalAtualCliente(
                (int) $request->input('id_cliente_servico'),
                $request->input('serial'),
                $request->boolean('forcar_refresh_tr069')
            );

            return response()->json([
                'message' => 'Sinal atual obtido com sucesso.',
                'sinal' => $sinal,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
