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

            if (config('services.nicon.two_factor')) {
                $payload['one_time_password'] = config('services.nicon.two_factor');
            }

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
}
