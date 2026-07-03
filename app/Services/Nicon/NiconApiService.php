<?php

namespace App\Services\Nicon;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NiconApiService
{
    private function baseUrl(): string
    {
        return config('services.nicon.base_url');
    }

    private function token(): string
    {
        return Cache::remember('nicon_api_token', 3600, function () {
            $payload = [
                'email' => config('services.nicon.email'),
                'password' => config('services.nicon.password'),
            ];

            $response = Http::timeout(config('services.nicon.timeout', 120))
                ->acceptJson()
                ->post("{$this->baseUrl()}/api/app-tecnico/login", $payload);

            if (!$response->successful()) {
                throw new RuntimeException('Login Nicon falhou: ' . $response->body());
            }

            $token = $response->json('user.api_token') ?? $response->json('token');

            if (!$token) {
                throw new RuntimeException('Login OK, mas token não veio na resposta.');
            }

            return $token;
        });
    }

    private function http()
    {
        return Http::timeout(config('services.nicon.timeout', 120))
            ->acceptJson()
            ->withToken($this->token());
    }

    public function buscarSinalOnu(int $idClienteServico, bool $tempoReal = false): array
    {
        $response = $this->http()->get(
            "{$this->baseUrl()}/api/app-tecnico/cliente-servico/buscar-sinal-onu/{$idClienteServico}",
            ['atualizacao_manual' => $tempoReal ? 1 : 0],
        );

        if ($response->status() === 401) {
            Cache::forget('nicon_api_token');
            return $this->buscarSinalOnu($idClienteServico, $tempoReal);
        }

        $response->throw();

        return $response->json();
    }

    /**
     * @param  array<int, int|string>  $idsClienteServico
     * @return array<int, array<string, mixed>>
     */
    public function buscarSinaisOnuParalelo(array $idsClienteServico): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsClienteServico),
            fn (int $id) => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $concorrencia = config('services.nicon.sinal_concorrencia', 8);
        $base = $this->baseUrl();
        $resultado = [];

        foreach (array_chunk($ids, $concorrencia) as $lote) {
            $token = $this->token();

            $respostas = Http::pool(function ($pool) use ($lote, $base, $token) {
                foreach ($lote as $id) {
                    $pool->as((string) $id)
                        ->timeout(config('services.nicon.timeout', 120))
                        ->acceptJson()
                        ->withToken($token)
                        ->get("{$base}/api/app-tecnico/cliente-servico/buscar-sinal-onu/{$id}", [
                            'atualizacao_manual' => 0,
                        ]);
                }
            });

            foreach ($lote as $id) {
                $response = $respostas[(string) $id] ?? null;

                if ($response?->status() === 401) {
                    Cache::forget('nicon_api_token');
                    continue;
                }

                if ($response && $response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        $resultado[$id] = $json;
                    }
                }
            }
        }

        return $resultado;
    }
}
