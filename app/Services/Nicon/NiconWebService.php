<?php

namespace App\Services\Nicon;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NiconWebService
{
    private const SESSION_CACHE_KEY = 'nicon_web_session';

    private function baseUrl(): string
    {
        return config('services.nicon.base_url');
    }

    /** @return array<int, int> */
    private function statusServicoPadrao(): array
    {
        $status = config('services.nicon.status_servico', [10, 12, 11, 13]);

        return array_values(array_map('intval', is_array($status) ? $status : [10, 12, 11, 13]));
    }

    /**
     * Fluxo da tela do Nicon: id_cidade + caixa → clientes → sinais.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscarSinaisPorCidadeECaixa(int $idCidade, string $nomeCaixa, ?int $idCaixaOptica = null): array
    {
        $clientes = $this->listarClientesPorCaixa($idCidade, $nomeCaixa, $idCaixaOptica);
        $ids = array_values(array_filter(array_map(
            fn (array $cliente) => $cliente['id_cliente_servico'] ?? null,
            $clientes
        )));

        if ($ids === []) {
            return $clientes;
        }

        $sinais = collect($this->buscarSinaisCompletos($ids, $this->montarMapaSeriais($clientes)))->keyBy('id_cliente_servico');

        $clientes = array_map(function (array $cliente) use ($sinais) {
            $id = (int) ($cliente['id_cliente_servico'] ?? 0);
            $sinal = $sinais->get($id);

            if (is_array($sinal)) {
                $cliente['sinal'] = $sinal['sinal'] ?? null;
                $cliente['serial_sinal'] = $sinal['serial'] ?? ($cliente['serial'] ?? null);
            }

            return $cliente;
        }, $clientes);

        return $this->enriquecerComStatusConexao($clientes);
    }

    /**
     * Preenche ultimo_uptime / ultimo_downtime a partir da API de conexão ONU.
     *
     * @param  array<int, array<string, mixed>>  $clientes
     * @return array<int, array<string, mixed>>
     */
    public function enriquecerComStatusConexao(array $clientes): array
    {
        $ids = array_values(array_filter(array_map(
            fn (array $cliente) => (int) ($cliente['id_cliente_servico'] ?? 0),
            $clientes
        )));

        if ($ids === []) {
            return $clientes;
        }

        $conexoes = $this->buscarStatusConexaoPorIds($ids);

        return array_map(function (array $cliente) use ($conexoes) {
            $id = (int) ($cliente['id_cliente_servico'] ?? 0);
            $conexao = $conexoes[$id] ?? null;

            if (! is_array($conexao)) {
                return $cliente;
            }

            if (! empty($conexao['ultimo_uptime'])) {
                $cliente['ultimo_uptime'] = $conexao['ultimo_uptime'];
            }
            if (! empty($conexao['ultimo_downtime'])) {
                $cliente['ultimo_downtime'] = $conexao['ultimo_downtime'];
            }
            if (array_key_exists('conectado', $conexao) && $conexao['conectado'] !== null) {
                $cliente['conectado'] = (bool) $conexao['conectado'];
            }
            if (! empty($conexao['status_conexao'])) {
                $cliente['status_conexao'] = $conexao['status_conexao'];
            }

            return $cliente;
        }, $clientes);
    }

    /**
     * Status de conexão (uptime/downtime) via API app-técnico.
     * A listagem de caixas não traz data_ultima_conexao — só a API de sinal ONU.
     *
     * @param  array<int, int|string>  $idsClienteServico
     * @return array<int, array{ultimo_uptime: ?string, ultimo_downtime: ?string, conectado: ?bool, status_conexao: ?string}>
     */
    public function buscarStatusConexaoPorIds(array $idsClienteServico): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsClienteServico),
            fn (int $id) => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        try {
            $respostas = app(NiconApiService::class)->buscarSinaisOnuParalelo($ids);
        } catch (RuntimeException) {
            return [];
        }

        $resultado = [];

        foreach ($respostas as $id => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $conexao = is_array($payload['conexao'] ?? null) ? $payload['conexao'] : [];
            $conectado = $conexao['conectado'] ?? $payload['conectado'] ?? null;

            $resultado[(int) $id] = [
                'ultimo_uptime' => $this->formatarTimestampNicon(
                    $conexao['data_ultima_conexao'] ?? null
                ),
                'ultimo_downtime' => $this->formatarTimestampNicon(
                    $conexao['data_ultima_desconexao'] ?? null
                ),
                'conectado' => is_bool($conectado) ? $conectado : null,
                'status_conexao' => isset($conexao['status_txt_resumido'])
                    ? trim((string) $conexao['status_txt_resumido'])
                    : (isset($conexao['conexao_texto_resumido'])
                        ? trim((string) $conexao['conexao_texto_resumido'])
                        : null),
            ];
        }

        return $resultado;
    }

    /**
     * GET /infra/buscar-caixas-com-cliente?id_cidade={id}
     *
     * @return array<string, mixed>
     */
    public function buscarCaixasComCliente(int $idCidade): array
    {
        return [
            'itens' => $this->listarCaixasDaCidade($idCidade),
        ];
    }

    /**
     * Lista de caixas da cidade em cache (evita baixar milhares de caixas a cada busca).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarCaixasDaCidade(int $idCidade): array
    {
        $ttl = config('services.nicon.caixas_cache_minutes', 360);

        return Cache::remember(
            "nicon_caixas_lista_{$idCidade}",
            now()->addMinutes($ttl),
            function () use ($idCidade) {
                $response = $this->getInfra('/infra/buscar-caixas-com-cliente', [
                    'id_cidade' => $idCidade,
                ]);

                return $this->extrairListaCaixas($response);
            }
        );
    }

    /**
     * Lista clientes vinculados à caixa (renderizar-caixas-proximas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarClientesPorCaixa(int $idCidade, string $nomeCaixa, ?int $idCaixaOptica = null): array
    {
        $caixa = $this->resolverCaixaOptica($idCidade, $nomeCaixa, $idCaixaOptica);

        if ($caixa === null) {
            throw new RuntimeException("Caixa \"{$nomeCaixa}\" não encontrada na cidade informada.");
        }

        $response = $this->renderizarCaixasProximas(
            (string) $caixa['nome'],
            (int) $caixa['id_caixa_optica']
        );

        return $this->extrairClientesDaRespostaCaixas($response, (string) $caixa['nome']);
    }

    /**
     * GET /cliente/conexao/renderizar-caixas-proximas
     *
     * @return array<string, mixed>
     */
    public function renderizarCaixasProximas(string $nomeCaixa, int $idCaixaOptica): array
    {
        $query = [
            'nome_caixa_selecionada' => $nomeCaixa,
            'id_caixa_optica' => $idCaixaOptica,
            'id_status_servico' => $this->statusServicoPadrao(),
        ];

        return $this->getCliente('/cliente/conexao/renderizar-caixas-proximas', $query);
    }

    /**
     * @return array{id_caixa_optica: int, nome: string}|null
     */
    public function resolverCaixaOptica(int $idCidade, string $nomeCaixa, ?int $idCaixaOptica = null): ?array
    {
        if ($idCaixaOptica !== null && $idCaixaOptica > 0) {
            foreach ($this->listarCaixasDaCidade($idCidade) as $caixa) {
                if ((int) ($caixa['id_caixa_optica'] ?? 0) === $idCaixaOptica) {
                    return [
                        'id_caixa_optica' => $idCaixaOptica,
                        'nome' => (string) ($caixa['nome'] ?? $this->formatarNomeCaixa($nomeCaixa)),
                    ];
                }
            }

            return [
                'id_caixa_optica' => $idCaixaOptica,
                'nome' => $this->formatarNomeCaixa($nomeCaixa),
            ];
        }

        $buscaNorm = $this->normalizarNomeCaixa($nomeCaixa);
        $cacheKey = 'nicon_caixa_resolve_' . $idCidade . '_' . md5($buscaNorm);
        $ttl = config('services.nicon.caixa_resolve_cache_minutes', 1440);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ! empty($cached['id_caixa_optica'])) {
            return $cached;
        }

        $caixa = $this->encontrarMelhorCaixa(
            $nomeCaixa,
            $this->filtrarCaixasParaBusca($nomeCaixa, $this->listarCaixasDaCidade($idCidade))
        );

        if ($caixa !== null) {
            Cache::put($cacheKey, $caixa, now()->addMinutes($ttl));
        }

        return $caixa;
    }

    /**
     * @param  array<int, array<string, mixed>>  $caixas
     * @return array{id_caixa_optica: int, nome: string}|null
     */
    private function encontrarMelhorCaixa(string $busca, array $caixas): ?array
    {
        $buscaNorm = $this->normalizarNomeCaixa($busca);
        $buscaCompacta = $this->compactarNomeCaixa($busca);

        if ($buscaNorm === '') {
            return null;
        }

        $candidatos = [];

        foreach ($caixas as $caixa) {
            $nome = (string) ($caixa['nome'] ?? $caixa['sigla'] ?? '');
            $id = (int) ($caixa['id_caixa_optica'] ?? $caixa['id'] ?? 0);

            if ($id <= 0 || $nome === '') {
                continue;
            }

            $score = $this->pontuarCorrespondenciaCaixa(
                $buscaNorm,
                $buscaCompacta,
                $this->normalizarNomeCaixa($nome),
                $this->compactarNomeCaixa($nome)
            );

            if ($score > 0) {
                $candidatos[] = [
                    'score' => $score,
                    'id_caixa_optica' => $id,
                    'nome' => $nome,
                ];
            }
        }

        if ($candidatos === []) {
            return null;
        }

        usort($candidatos, function (array $a, array $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return strlen($a['nome']) <=> strlen($b['nome']);
        });

        $melhorScore = $candidatos[0]['score'];
        $melhores = array_values(array_filter(
            $candidatos,
            fn (array $c) => $c['score'] === $melhorScore
        ));

        if (count($melhores) > 1 && $melhorScore < 100) {
            $nomes = array_map(fn (array $c) => $c['nome'], array_slice($melhores, 0, 6));
            $lista = implode(', ', $nomes);
            $sufixo = count($melhores) > 6 ? '...' : '';

            throw new RuntimeException(
                "Várias caixas correspondem a \"{$busca}\". Seja mais específico. Ex.: {$lista}{$sufixo}"
            );
        }

        return [
            'id_caixa_optica' => $candidatos[0]['id_caixa_optica'],
            'nome' => $candidatos[0]['nome'],
        ];
    }

    /**
     * Consulta sinais em lote no Nicon.
     * POST /cliente/atendimento/buscar-sinal-cliente
     *
     * @param  array<int, string|int>  $idsClienteServico
     * @return array<int, array{sinal: mixed, id_cliente_servico: int, serial: string}>
     */
    public function buscarSinalClientes(array $idsClienteServico): array
    {
        $ids = array_values(array_map(
            fn ($id) => (string) $id,
            array_filter($idsClienteServico, fn ($id) => $id !== null && $id !== '')
        ));

        if ($ids === []) {
            return [];
        }

        return $this->buscarSinalCliente([
            'clientesServicos' => $ids,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<int, array{sinal: mixed, id_cliente_servico: int, serial: string}>
     */
    public function buscarSinalCliente(array $filtros): array
    {
        $response = $this->postCliente('/cliente/atendimento/buscar-sinal-cliente', $filtros);

        return is_array($response) ? array_values($response) : [];
    }

    /**
     * Sinal RX atual de um cliente/ONU individual.
     * POST /cliente/atendimento/buscar-sinal-atual-cliente
     *
     * @return array{rx?: string, data_atualizacao?: string}
     */
    public function buscarSinalAtualCliente(
        int $idClienteServico,
        ?string $serial = null,
        bool $forcarRefreshTr069 = false
    ): array {
        $response = $this->postCliente(
            '/cliente/atendimento/buscar-sinal-atual-cliente',
            $this->montarPayloadSinalAtualCliente($idClienteServico, $serial, $forcarRefreshTr069)
        );

        return is_array($response) ? $response : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function montarPayloadSinalAtualCliente(
        int $idClienteServico,
        ?string $serial = null,
        bool $forcarRefreshTr069 = false
    ): array {
        $payload = [
            'id_cliente_servico' => $idClienteServico,
        ];

        $serial = trim((string) $serial);

        if ($serial !== '') {
            $payload['serial'] = $serial;
        }

        if ($forcarRefreshTr069) {
            $payload['forcar_refresh_tr069'] = 1;
        }

        return $payload;
    }

    /**
     * Busca sinais em lote e preenche RX quando vier null na listagem.
     *
     * @param  array<int, string|int>  $idsClienteServico
     * @return array<int, array<string, mixed>>
     */
    public function buscarSinaisCompletos(array $idsClienteServico, array $seriaisPorId = []): array
    {
        $ids = array_values(array_map(
            fn ($id) => (int) $id,
            array_filter($idsClienteServico, fn ($id) => $id !== null && $id !== '')
        ));

        if ($ids === []) {
            return [];
        }

        $sinaisWeb = $this->buscarSinaisAtuaisParalelo($ids, $seriaisPorId);

        return array_map(function (int $id) use ($sinaisWeb, $seriaisPorId) {
            return [
                'id_cliente_servico' => $id,
                'serial' => $seriaisPorId[$id] ?? '',
                'sinal' => $sinaisWeb[$id] ?? null,
            ];
        }, $ids);
    }

    /**
     * @param  array<int, array<string, mixed>>  $clientes
     * @return array<int, string>
     */
    private function montarMapaSeriais(array $clientes): array
    {
        $mapa = [];

        foreach ($clientes as $cliente) {
            if (! is_array($cliente)) {
                continue;
            }

            $id = (int) ($cliente['id_cliente_servico'] ?? 0);
            $serial = trim((string) ($cliente['serial'] ?? ''));

            if ($id > 0 && $serial !== '') {
                $mapa[$id] = $serial;
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, int>  $idsClienteServico
     * @param  array<int, string>  $seriaisPorId
     * @return array<int, array{rx?: string, data_atualizacao?: string}>
     */
    private function buscarSinaisAtuaisParalelo(array $idsClienteServico, array $seriaisPorId = []): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsClienteServico),
            fn (int $id) => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $resultado = [];
        $pendentes = $ids;
        $maxTentativas = config('services.nicon.sinal_tentativas', 3);

        for ($tentativa = 1; $tentativa <= $maxTentativas && $pendentes !== []; $tentativa++) {
            if ($tentativa > 1) {
                usleep(500_000);
            }

            $sessaoInvalida = false;

            foreach (array_chunk($pendentes, config('services.nicon.sinal_concorrencia', 4)) as $lote) {
                [$loteOk, $loteSessaoInvalida] = $this->consultarLoteSinaisAtuais($lote, $seriaisPorId);
                $resultado += $loteOk;
                $sessaoInvalida = $sessaoInvalida || $loteSessaoInvalida;
            }

            if ($sessaoInvalida) {
                Cache::forget(self::SESSION_CACHE_KEY);
            }

            $pendentes = array_values(array_filter(
                $pendentes,
                fn (int $id) => ! $this->possuiSinalConfiavel($resultado[$id] ?? null)
            ));
        }

        foreach ($pendentes as $id) {
            if ($this->possuiSinalConfiavel($resultado[$id] ?? null)) {
                continue;
            }

            try {
                $json = $this->buscarSinalAtualCliente(
                    $id,
                    $seriaisPorId[$id] ?? null,
                    false
                );
                if ($this->respostaSinalAtualValida($json)) {
                    $resultado[$id] = $json;
                }
            } catch (RuntimeException) {
                // Nicon indisponível para este cliente — segue sem sinal.
            }
        }

        $precisamRefresh = array_values(array_filter(
            $ids,
            fn (int $id) => ! $this->possuiSinalConfiavel($resultado[$id] ?? null)
        ));

        foreach ($precisamRefresh as $id) {
            try {
                $json = $this->buscarSinalAtualCliente(
                    $id,
                    $seriaisPorId[$id] ?? null,
                    true
                );
                if ($this->respostaSinalAtualValida($json)) {
                    $resultado[$id] = $json;
                }
            } catch (RuntimeException) {
                // Mantém sem sinal após refresh individual.
            }
        }

        return $resultado;
    }

    /**
     * @param  array<int, int>  $lote
     * @param  array<int, string>  $seriaisPorId
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    private function consultarLoteSinaisAtuais(array $lote, array $seriaisPorId = []): array
    {
        $resultado = [];
        $sessaoInvalida = false;
        $sessao = $this->obterSessaoWeb();
        $base = $this->baseUrl();
        $jar = $this->montarCookieJar($sessao);

        $respostas = Http::pool(function ($pool) use ($lote, $sessao, $base, $jar, $seriaisPorId) {
            foreach ($lote as $id) {
                $pool->as((string) $id)
                    ->timeout(config('services.nicon.timeout', 120))
                    ->acceptJson()
                    ->asJson()
                    ->withOptions(['cookies' => $jar])
                    ->withHeaders([
                        'X-XSRF-TOKEN' => $sessao['xsrf'],
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Referer' => $base . '/cliente/atendimento',
                    ])
                    ->post("{$base}/cliente/atendimento/buscar-sinal-atual-cliente", $this->montarPayloadSinalAtualCliente(
                        $id,
                        $seriaisPorId[$id] ?? null,
                        false
                    ));
            }
        });

        foreach ($lote as $id) {
            $response = $respostas[(string) $id] ?? null;

            if ($response instanceof ConnectionException || $response instanceof RequestException) {
                continue;
            }

            if (! $response instanceof Response) {
                continue;
            }

            if (in_array($response->status(), [401, 419], true)) {
                $sessaoInvalida = true;
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $json = $response->json();

            if ($this->respostaSinalAtualValida($json)) {
                $resultado[$id] = $json;
            }
        }

        return [$resultado, $sessaoInvalida];
    }

    /** @param  mixed  $json */
    private function respostaSinalAtualValida($json): bool
    {
        return is_array($json)
            && $json !== []
            && $this->possuiSinalConfiavel($json);
    }

    /** @param  mixed  $sinal */
    private function possuiSinalConfiavel($sinal): bool
    {
        if (! is_array($sinal) || ! array_key_exists('rx', $sinal)) {
            return false;
        }

        return $this->rxUtil($sinal['rx']);
    }

    private function rxUtil(mixed $rx): bool
    {
        if ($rx === null || $rx === '') {
            return false;
        }

        if (is_numeric($rx) && (float) $rx === 0.0) {
            return false;
        }

        return true;
    }

    /**
     * Reduz o universo de caixas antes de pontuar correspondência.
     *
     * @param  array<int, array<string, mixed>>  $caixas
     * @return array<int, array<string, mixed>>
     */
    private function filtrarCaixasParaBusca(string $busca, array $caixas): array
    {
        $buscaNorm = $this->normalizarNomeCaixa($busca);
        $buscaCompacta = $this->compactarNomeCaixa($busca);

        if ($buscaNorm === '' || strlen($buscaCompacta) < 2) {
            return $caixas;
        }

        $filtradas = array_values(array_filter($caixas, function (array $caixa) use ($buscaNorm, $buscaCompacta) {
            $nome = (string) ($caixa['nome'] ?? $caixa['sigla'] ?? '');
            $nomeNorm = $this->normalizarNomeCaixa($nome);
            $nomeCompacto = $this->compactarNomeCaixa($nome);

            return str_contains($nomeNorm, $buscaNorm)
                || str_contains($nomeCompacto, $buscaCompacta);
        }));

        return $filtradas !== [] ? $filtradas : $caixas;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function extrairClientesDaRespostaCaixas(array $response, string $nomeCaixa): array
    {
        $caixas = $response['caixas'] ?? [];

        if (! is_array($caixas) || $caixas === []) {
            return [];
        }

        $dadosCaixa = $caixas[$nomeCaixa] ?? null;

        if (! is_array($dadosCaixa)) {
            foreach ($caixas as $nome => $dados) {
                if (! is_array($dados)) {
                    continue;
                }

                if ($this->nomesCaixaCoincidem($this->normalizarNomeCaixa($nomeCaixa), (string) $nome)) {
                    $dadosCaixa = $dados;
                    break;
                }
            }
        }

        if (! is_array($dadosCaixa)) {
            $dadosCaixa = reset($caixas);
        }

        if (! is_array($dadosCaixa)) {
            return [];
        }

        $clientes = [];

        foreach (($dadosCaixa['clientes'] ?? []) as $cliente) {
            if (! is_array($cliente)) {
                continue;
            }

            foreach (($cliente['servicos'] ?? []) as $servico) {
                if (! is_array($servico)) {
                    continue;
                }

                $porta = $servico['cliente_porta_atendimento']['mapeamento_porta_atendimento']['sequencia'] ?? null;
                $porta = is_numeric($porta) ? ((int) $porta) + 1 : null;

                $clientes[] = [
                    'id_cliente_servico' => (int) ($servico['id_cliente_servico'] ?? 0),
                    'id_cliente' => (int) ($cliente['id_cliente'] ?? $servico['id_cliente'] ?? 0),
                    'codigo_cliente' => (int) ($cliente['codigo_cliente'] ?? 0),
                    'nome' => (string) ($cliente['nome_razaosocial'] ?? ''),
                    'serial' => (string) ($servico['serial'] ?? ''),
                    'conectado' => (bool) ($servico['conectado'] ?? false),
                    'porta' => $porta,
                    'status_servico' => $servico['status_servico']['descricao'] ?? null,
                    'lacre' => $this->extrairLacreServico($servico),
                    // Na listagem da caixa o Nicon só traz desconexão; uptime vem da API de conexão.
                    'ultimo_uptime' => $this->formatarTimestampNicon(
                        $servico['data_ultima_conexao'] ?? null
                    ),
                    'ultimo_downtime' => $this->formatarTimestampNicon(
                        $servico['data_ultima_desconexao'] ?? null
                    ),
                    'caixa' => $dadosCaixa['sigla'] ?? $nomeCaixa,
                    'id_caixa_optica' => (int) ($dadosCaixa['id_caixa_optica'] ?? 0),
                ];
            }
        }

        usort($clientes, fn (array $a, array $b) => ($a['porta'] ?? 0) <=> ($b['porta'] ?? 0));

        return $clientes;
    }

    /** @param  array<string, mixed>  $servico */
    private function extrairLacreServico(array $servico): ?string
    {
        // Ordem de preferência (caminhos conhecidos do Nicon).
        $candidatos = [
            $servico['lacre'] ?? null,
            $servico['numero_lacre'] ?? null,
            $servico['cliente_servico_local']['lacre'] ?? null,
            $servico['cliente_servico_local']['numero_lacre'] ?? null,
            $servico['cliente_porta_atendimento']['lacre'] ?? null,
            $servico['cliente_porta_atendimento']['numero_lacre'] ?? null,
            $servico['cliente_porta_atendimento']['mapeamento_porta_atendimento']['lacre'] ?? null,
        ];

        foreach ($candidatos as $valor) {
            $lacre = $this->normalizarLacre($valor);
            if ($lacre !== null) {
                return $lacre;
            }
        }

        // Fallback: qualquer chave *lacre* no payload do serviço.
        foreach ($this->coletarValoresPorChave($servico, 'lacre') as $valor) {
            $lacre = $this->normalizarLacre($valor);
            if ($lacre !== null) {
                return $lacre;
            }
        }

        return null;
    }

    private function normalizarLacre(mixed $valor): ?string
    {
        if ($valor === null || $valor === false) {
            return null;
        }

        // Inteiro/float válido (ex.: 8480).
        if (is_int($valor) || is_float($valor)) {
            if ((int) $valor <= 0) {
                return null;
            }

            return (string) (int) $valor;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        // Placeholders do Nicon: " - ", "-", "—", "N/A", etc.
        $textoNorm = mb_strtolower($texto);
        if (in_array($textoNorm, ['-', '–', '—', 'n/a', 'na', 'null', 'none', 'sem lacre'], true)) {
            return null;
        }

        // Só traços/espaços/pontos (ex.: " - ", "...").
        if (preg_match('/^[\s.\-_–—]+$/u', $texto)) {
            return null;
        }

        return $texto;
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<int, mixed>
     */
    private function coletarValoresPorChave(array $dados, string $trechoChave): array
    {
        $encontrados = [];
        $trecho = mb_strtolower($trechoChave);

        $walk = function ($no) use (&$walk, &$encontrados, $trecho): void {
            if (! is_array($no)) {
                return;
            }

            foreach ($no as $chave => $valor) {
                if (is_string($chave) && str_contains(mb_strtolower($chave), $trecho)) {
                    $encontrados[] = $valor;
                }

                if (is_array($valor)) {
                    $walk($valor);
                }
            }
        };

        $walk($dados);

        return $encontrados;
    }

    private function formatarTimestampNicon(mixed $valor): ?string
    {
        if ($valor === null || $valor === '' || $valor === false) {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d/m/Y H:i');
        }

        if (is_numeric($valor)) {
            $numero = (float) $valor;
            // Timestamp em segundos ou milissegundos.
            if ($numero > 1_000_000_000_000) {
                $numero = (int) round($numero / 1000);
            } else {
                $numero = (int) $numero;
            }

            if ($numero < 946684800) { // antes de 2000-01-01
                return null;
            }

            return date('d/m/Y H:i', $numero);
        }

        $texto = trim((string) $valor);
        if ($texto === '' || $texto === '0' || $texto === '0000-00-00' || str_starts_with($texto, '0000-00-00')) {
            return null;
        }

        // Nicon às vezes manda timezone sem dois-pontos: 2026-06-23 09:39:52-03
        $texto = preg_replace('/([+-]\d{2})$/', '$1:00', $texto) ?? $texto;

        try {
            return (new \DateTimeImmutable($texto))->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $texto;
        }
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function extrairListaCaixas(array $response): array
    {
        if (isset($response['itens']) && is_array($response['itens'])) {
            return $this->mapearItensCaixa($response['itens']);
        }

        if (isset($response['caixas']) && is_array($response['caixas'])) {
            $lista = [];

            foreach ($response['caixas'] as $chave => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $lista[] = [
                    'nome' => (string) ($item['nome'] ?? $item['sigla'] ?? $chave),
                    'sigla' => (string) ($item['sigla'] ?? $item['nome'] ?? $chave),
                    'id_caixa_optica' => (int) ($item['id_caixa_optica'] ?? $item['id'] ?? 0),
                ];
            }

            return $lista;
        }

        $candidatos = $response['data'] ?? $response;

        if (! is_array($candidatos)) {
            return [];
        }

        if (! array_is_list($candidatos)) {
            $candidatos = array_values($candidatos);
        }

        $lista = [];

        foreach ($candidatos as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) ($item['id_caixa_optica'] ?? $item['id'] ?? 0);
            $nome = (string) ($item['nome'] ?? $item['sigla'] ?? $item['descricao'] ?? '');

            if ($id > 0 && $nome !== '') {
                $lista[] = [
                    'nome' => $nome,
                    'sigla' => (string) ($item['sigla'] ?? $nome),
                    'id_caixa_optica' => $id,
                ];
            }
        }

        return $lista;
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @return array<int, array<string, mixed>>
     */
    private function mapearItensCaixa(array $itens): array
    {
        $lista = [];

        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) ($item['id_caixa_optica'] ?? $item['id'] ?? 0);
            $nome = (string) ($item['nome'] ?? $item['sigla'] ?? '');

            if ($id > 0 && $nome !== '') {
                $lista[] = [
                    'nome' => $nome,
                    'sigla' => (string) ($item['sigla'] ?? $nome),
                    'id_caixa_optica' => $id,
                ];
            }
        }

        return $lista;
    }

    private function normalizarNomeCaixa(string $nome): string
    {
        $nome = trim($nome);
        $nome = preg_replace('/^caixa[-_]?/i', '', $nome) ?? $nome;

        return strtoupper(str_replace([' ', '-'], '_', $nome));
    }

    private function formatarNomeCaixa(string $nome): string
    {
        $nome = trim($nome);

        if (preg_match('/^caixa[-_]/i', $nome)) {
            return $nome;
        }

        return 'Caixa-' . ltrim($nome, '-');
    }

    private function nomesCaixaCoincidem(string $busca, string $nome): bool
    {
        return $this->pontuarCorrespondenciaCaixa(
            $this->normalizarNomeCaixa($busca),
            $this->compactarNomeCaixa($busca),
            $this->normalizarNomeCaixa($nome),
            $this->compactarNomeCaixa($nome)
        ) >= 70;
    }

    private function compactarNomeCaixa(string $nome): string
    {
        return preg_replace('/[^A-Z0-9]/', '', $this->normalizarNomeCaixa($nome)) ?? '';
    }

    private function pontuarCorrespondenciaCaixa(
        string $buscaNorm,
        string $buscaCompacta,
        string $nomeNorm,
        string $nomeCompacto
    ): int {
        if ($buscaNorm === $nomeNorm) {
            return 100;
        }

        if ($buscaCompacta !== '' && $buscaCompacta === $nomeCompacto) {
            return 95;
        }

        if (str_starts_with($nomeNorm, $buscaNorm . '_')) {
            return 85;
        }

        if (str_starts_with($nomeNorm, $buscaNorm)) {
            return 80;
        }

        if (str_contains($nomeNorm, $buscaNorm)) {
            return 70;
        }

        if ($buscaCompacta !== '' && str_contains($nomeCompacto, $buscaCompacta)) {
            return 60;
        }

        return 0;
    }

    /** @return array<string, mixed> */
    private function getInfra(string $path, array $query): array
    {
        return $this->getComSessao($path, $query, '/infra');
    }

    /** @return array<string, mixed> */
    private function getCliente(string $path, array $query): array
    {
        return $this->getComSessao($path, $query, '/cliente/conexao');
    }

    /** @return array<string, mixed> */
    private function getComSessao(string $path, array $query, string $refererPath): array
    {
        $response = $this->httpComSessao($refererPath)->get($this->baseUrl() . $path, $query);

        if (in_array($response->status(), [401, 419], true)) {
            Cache::forget(self::SESSION_CACHE_KEY);
            $response = $this->httpComSessao($refererPath)->get($this->baseUrl() . $path, $query);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "Nicon {$path} falhou (HTTP {$response->status()}): " . $response->body()
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /** @return array<string, mixed> */
    private function postCliente(string $path, array $body): array
    {
        $response = $this->httpComSessao('/cliente/atendimento')->post($this->baseUrl() . $path, $body);

        if (in_array($response->status(), [401, 419], true)) {
            Cache::forget(self::SESSION_CACHE_KEY);
            $response = $this->httpComSessao('/cliente/atendimento')->post($this->baseUrl() . $path, $body);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "Nicon {$path} falhou (HTTP {$response->status()}): " . $response->body()
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function httpComSessao(string $refererPath = '/cliente/atendimento')
    {
        $sessao = $this->obterSessaoWeb();

        return Http::timeout(config('services.nicon.timeout', 120))
            ->acceptJson()
            ->withOptions(['cookies' => $this->montarCookieJar($sessao)])
            ->withHeaders([
                'X-XSRF-TOKEN' => $sessao['xsrf'],
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $this->baseUrl() . $refererPath,
            ]);
    }

    /** @return array{cookies: array<int, array<string, mixed>>, xsrf: string} */
    private function obterSessaoWeb(): array
    {
        $cached = Cache::get(self::SESSION_CACHE_KEY);

        if (is_array($cached) && ! empty($cached['cookies']) && ! empty($cached['xsrf'])) {
            return $cached;
        }

        $sessao = $this->autenticarSessaoWeb();
        Cache::put(self::SESSION_CACHE_KEY, $sessao, now()->addMinutes(50));

        return $sessao;
    }

    /** @return array{cookies: array<int, array<string, mixed>>, xsrf: string} */
    private function autenticarSessaoWeb(): array
    {
        $jar = new CookieJar;
        $base = $this->baseUrl();

        $loginPage = Http::timeout(config('services.nicon.timeout', 120))
            ->withOptions(['cookies' => $jar, 'allow_redirects' => true])
            ->get("{$base}/login");

        if (! $loginPage->successful()) {
            throw new RuntimeException('Nicon: não foi possível abrir a página de login.');
        }

        $xsrf = $this->extrairXsrf($jar);

        if ($xsrf === '') {
            throw new RuntimeException('Nicon: XSRF-TOKEN não encontrado após abrir /login.');
        }

        $credenciais = [
            'email' => config('services.nicon.email'),
            'password' => config('services.nicon.password'),
        ];

        if (config('services.nicon.two_factor')) {
            $credenciais['one_time_password'] = config('services.nicon.two_factor');
        }

        $login = Http::timeout(config('services.nicon.timeout', 120))
            ->acceptJson()
            ->asJson()
            ->withOptions(['cookies' => $jar, 'allow_redirects' => true])
            ->withHeaders([
                'X-XSRF-TOKEN' => $xsrf,
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => "{$base}/login",
            ])
            ->post("{$base}/login", $credenciais);

        if (! $login->successful() && $login->status() !== 204) {
            $loginUsuario = Http::timeout(config('services.nicon.timeout', 120))
                ->acceptJson()
                ->asJson()
                ->withOptions(['cookies' => $jar, 'allow_redirects' => true])
                ->withHeaders([
                    'X-XSRF-TOKEN' => $this->extrairXsrf($jar) ?: $xsrf,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => "{$base}/login",
                ])
                ->post("{$base}/login", [
                    'usuario' => config('services.nicon.email'),
                    'password' => config('services.nicon.password'),
                ]);

            if (! $loginUsuario->successful() && $loginUsuario->status() !== 204) {
                throw new RuntimeException(
                    'Nicon: login web falhou (HTTP ' . $login->status() . '): ' . $login->body()
                );
            }
        }

        $xsrfFinal = $this->extrairXsrf($jar);

        if ($xsrfFinal === '') {
            throw new RuntimeException('Nicon: sessão web sem XSRF-TOKEN após login.');
        }

        return [
            'cookies' => $jar->toArray(),
            'xsrf' => $xsrfFinal,
        ];
    }

    private function extrairXsrf(CookieJar $jar): string
    {
        foreach ($jar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'XSRF-TOKEN') {
                return urldecode((string) ($cookie['Value'] ?? ''));
            }
        }

        return '';
    }

    /** @param  array{cookies: array<int, array<string, mixed>>, xsrf: string}  $sessao */
    private function montarCookieJar(array $sessao): CookieJar
    {
        return CookieJar::fromArray(
            collect($sessao['cookies'])->mapWithKeys(
                fn (array $cookie) => [($cookie['Name'] ?? '') => $cookie['Value'] ?? '']
            )->filter(fn ($value, $name) => $name !== '')->all(),
            parse_url($this->baseUrl(), PHP_URL_HOST) ?: ''
        );
    }
}
