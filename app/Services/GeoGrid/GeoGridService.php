<?php

namespace App\Services\GeoGrid;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeoGridService
{
    private const SESSION_CACHE_KEY = 'geogrid_api_session';

    private function baseUrl(): string
    {
        return rtrim((string) config('services.geogrid.base_url'), '/');
    }

    private function apiUrl(): string
    {
        return $this->baseUrl() . '/api/v3';
    }

    private function authUrl(): string
    {
        $custom = trim((string) config('services.geogrid.auth_url'));

        return $custom !== '' ? $custom : $this->apiUrl() . '/autenticar';
    }

    private function version(): string
    {
        return (string) config('services.geogrid.version', '199.5');
    }

    private function timeout(): int
    {
        return (int) config('services.geogrid.timeout', 120);
    }

    /** @return array{authorization: string, dados: array<string, mixed>, dados_locais: array<string, mixed>} */
    private function obterSessao(): array
    {
        $cached = Cache::get(self::SESSION_CACHE_KEY);

        if (is_array($cached) && ! empty($cached['authorization'])) {
            return $cached;
        }

        $sessao = $this->autenticar();
        Cache::put(self::SESSION_CACHE_KEY, $sessao, now()->addMinutes(50));

        return $sessao;
    }

    /** @return array{authorization: string, dados: array<string, mixed>, dados_locais: array<string, mixed>} */
    private function autenticar(): array
    {
        $usuario = (string) config('services.geogrid.user');
        $senha = (string) config('services.geogrid.password');

        if ($usuario === '' || $senha === '') {
            throw new RuntimeException('GeoGrid: credenciais não configuradas (GEOGRID_USER / GEOGRID_PASSWORD).');
        }

        $response = Http::timeout($this->timeout())
            ->withOptions(['verify' => config('services.http_verify_ssl', true)])
            ->acceptJson()
            ->asJson()
            ->withHeaders(['Geogrid-Version' => $this->version()])
            ->post($this->authUrl(), [
                'usuario' => $usuario,
                'senha' => $senha,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('GeoGrid: falha no login (HTTP ' . $response->status() . ').');
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['autenticacao'])) {
            $mensagem = is_array($data) ? (string) ($data['resposta'] ?? $data['message'] ?? 'Login inválido.') : 'Resposta inválida.';

            throw new RuntimeException('GeoGrid: ' . $mensagem);
        }

        $authorization = (string) $data['autenticacao'];
        $dadosLocais = $this->inicializarSessaoApi($authorization, $data);

        return [
            'authorization' => $authorization,
            'dados' => is_array($data['dados'] ?? null) ? $data['dados'] : [],
            'dados_locais' => $dadosLocais,
        ];
    }

    /**
     * @param  array<string, mixed>  $authData
     * @return array<string, mixed>
     */
    private function inicializarSessaoApi(string $authorization, array $authData): array
    {
        $dados = is_array($authData['dados'] ?? null) ? $authData['dados'] : [];
        $cidade = (string) ($dados['cidade'] ?? '');
        $estado = (string) ($dados['estado'] ?? '');

        if ($cidade === '') {
            return [];
        }

        $params = ['cidade' => $cidade];
        if ($estado !== '') {
            $params['estado'] = $estado;
        }

        $response = Http::timeout($this->timeout())
            ->withOptions(['verify' => config('services.http_verify_ssl', true)])
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $authorization,
                'Geogrid-Version' => $this->version(),
                'Referer' => $this->baseUrl() . '/',
            ])
            ->get($this->apiUrl() . '/login', $params);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : [];
    }

    private function client(bool $renovar = false): PendingRequest
    {
        if ($renovar) {
            Cache::forget(self::SESSION_CACHE_KEY);
        }

        $sessao = $this->obterSessao();

        return Http::timeout($this->timeout())
            ->withOptions(['verify' => config('services.http_verify_ssl', true)])
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $sessao['authorization'],
                'Geogrid-Version' => $this->version(),
                'Referer' => $this->baseUrl() . '/',
            ]);
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, string $contexto): array
    {
        if ($response->status() === 401) {
            Cache::forget(self::SESSION_CACHE_KEY);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "GeoGrid: {$contexto} falhou (HTTP {$response->status()}): " . $response->body()
            );
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * GET /menu/itens
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function buscarItensMenu(array $params = []): array
    {
        $sessao = $this->obterSessao();
        $params = $this->enriquecerParamsMenu($params, $sessao);

        try {
            $response = $this->requestApi('get', '/menu/itens', $params);

            return $this->decode($response, 'busca de itens')['registros'] ?? [];
        } catch (RuntimeException) {
            try {
                return $this->buscarItensMenuMapa($params);
            } catch (RuntimeException) {
                return [];
            }
        }
    }

    /**
     * Fallback: GET /menu/itens/mapa
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    private function buscarItensMenuMapa(array $params): array
    {
        $params = $this->enriquecerParamsMenu($params, $this->obterSessao());
        $params['modo'] = 'plotar-itens';

        if (isset($params['grupos']) && is_array($params['grupos'])) {
            return $this->decode(
                $this->requestApiGet('/menu/itens/mapa?' . $this->montarQueryMenuMapa($params)),
                'busca de itens no mapa'
            )['registros'] ?? [];
        }

        $response = $this->requestApi('get', '/menu/itens/mapa', $params);

        return $this->decode($response, 'busca de itens no mapa')['registros'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function montarQueryMenuMapa(array $params): string
    {
        $partes = [];

        foreach ($params as $chave => $valor) {
            if ($chave === 'grupos' && is_array($valor)) {
                foreach ($valor as $indice => $grupo) {
                    if (! is_array($grupo)) {
                        continue;
                    }
                    foreach ($grupo as $campo => $campoValor) {
                        $partes[] = rawurlencode("grupos[{$indice}][{$campo}]") . '=' . rawurlencode((string) $campoValor);
                    }
                }
                continue;
            }

            if ($chave === 'modoProjeto' && is_array($valor)) {
                foreach ($valor as $modo) {
                    $partes[] = 'modoProjeto%5B%5D=' . rawurlencode((string) $modo);
                }
                continue;
            }

            if ($chave === 'itens' && is_array($valor)) {
                foreach ($valor as $item) {
                    $partes[] = 'itens%5B%5D=' . rawurlencode((string) $item);
                }
                continue;
            }

            if (is_array($valor)) {
                continue;
            }

            $partes[] = rawurlencode($chave) . '=' . rawurlencode((string) $valor);
        }

        return implode('&', $partes);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array{authorization: string, dados: array<string, mixed>, dados_locais: array<string, mixed>}  $sessao
     * @return array<string, mixed>
     */
    private function enriquecerParamsMenu(array $params, array $sessao): array
    {
        $params += [
            'subPastas' => 'S',
            'recipientes' => 'S',
        ];

        $idCidade = $sessao['dados_locais']['dados']['idCidade']
            ?? $sessao['dados']['idCidade']
            ?? null;

        if ($idCidade !== null && ! isset($params['idCidade'])) {
            $params['idCidade'] = $idCidade;
        }

        if (! isset($params['modoProjeto'])) {
            $params['modoProjeto'] = ['N'];
        }

        return $params;
    }

    /**
     * Busca caixa pelo ID interno do GeoGrid.
     *
     * @return array<string, mixed>|null
     */
    public function buscarCaixaPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $mapa = $this->obterMapaItens([$id]);
        $registroMapa = $mapa[0] ?? $this->obterMapaItem($id);

        $ficha = null;
        try {
            $ficha = $this->obterFichaItem($id);
        } catch (RuntimeException) {
        }

        return $this->normalizarCaixa(
            ['id' => $id, 'sigla' => $registroMapa['dados']['sigla'] ?? ''],
            $registroMapa,
            $ficha
        );
    }

    /** @param  array<string, mixed>|null  $caixa */
    private function caixaValida(?array $caixa): bool
    {
        return is_array($caixa)
            && ! empty($caixa['id'])
            && $caixa['latitude'] !== null
            && $caixa['longitude'] !== null;
    }

    private function requestApiGet(string $pathComQuery): Response
    {
        $url = $this->apiUrl() . $pathComQuery;
        $response = $this->client()->get($url);

        if ($response->status() === 401) {
            $response = $this->client(true)->get($url);
        }

        return $response;
    }

    /**
     * GET /itensRede/mapa?ids[]=...
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, array<string, mixed>>
     */
    public function obterMapaItens(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $query = implode('&', array_map(
            fn (int $id) => 'ids[]=' . rawurlencode((string) $id),
            $ids
        ));

        $response = $this->requestApiGet('/itensRede/mapa?' . $query);

        return $this->decode($response, 'mapa de itens')['registros'] ?? [];
    }

    /**
     * GET /itensRede/{id}/mapa
     *
     * @return array<string, mixed>
     */
    public function obterMapaItem(int $id): array
    {
        $response = $this->requestApi('get', "/itensRede/{$id}/mapa");

        return $this->decode($response, 'mapa do item');
    }

    /**
     * GET /itensRede/{id}/ficha
     *
     * @return array<string, mixed>
     */
    public function obterFichaItem(int $id): array
    {
        $response = $this->requestApi('get', "/itensRede/{$id}/ficha");

        return $this->decode($response, 'ficha do item');
    }

    /**
     * Busca caixa por termo (sigla, código Nicon, etc.) e retorna localização.
     *
     * @return array<string, mixed>|null
     */
    public function buscarCaixaPorTermo(string $termo): ?array
    {
        $termo = trim($termo);

        if ($termo === '') {
            return null;
        }

        if (ctype_digit($termo)) {
            $porId = $this->buscarCaixaPorId((int) $termo);
            if ($this->caixaValida($porId)) {
                return $porId;
            }
        }

        $candidatos = $this->buscarCandidatosCaixa($termo);

        if ($candidatos === []) {
            return $this->buscarCaixaPorSiglaNoMapa($termo);
        }

        $melhor = $this->escolherMelhorCaixa($termo, $candidatos);
        $id = (int) ($melhor['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        $mapa = $this->obterMapaItens([$id]);
        $registroMapa = $mapa[0] ?? $this->obterMapaItem($id);

        $ficha = null;
        try {
            $ficha = $this->obterFichaItem($id);
        } catch (RuntimeException) {
            // ficha opcional — mapa já traz lat/lng
        }

        return $this->normalizarCaixa($melhor, $registroMapa, $ficha);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarCandidatosCaixa(string $termo): array
    {
        $termoBusca = $this->normalizarTermoBusca($termo);
        $encontrados = [];

        foreach ($this->montarConsultasBusca($termo, $termoBusca) as $params) {
            $itens = isset($params['grupos'])
                ? $this->buscarItensMenuMapa($params)
                : $this->buscarItensMenu($params);

            foreach ($itens as $item) {
                if (($item['item'] ?? '') !== 'caixa') {
                    continue;
                }

                $id = (int) ($item['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $encontrados[$id] = $item;
            }

            if (count($encontrados) >= 25) {
                break;
            }
        }

        return array_values($encontrados);
    }

    private function buscarCaixaPorSiglaNoMapa(string $termo): ?array
    {
        $idPasta = (int) config('services.geogrid.pasta_caixas', 0);
        if ($idPasta <= 0) {
            return null;
        }

        try {
            $itens = $this->buscarItensMenuMapa([
                'pesquisa' => '%' . $termo . '%',
                'grupos' => [
                    ['idPasta' => $idPasta, 'item' => 'caixa'],
                ],
            ]);
        } catch (RuntimeException) {
            return null;
        }

        $candidatos = array_values(array_filter(
            $itens,
            fn (array $item) => ($item['item'] ?? '') === 'caixa'
        ));

        if ($candidatos === []) {
            return null;
        }

        $melhor = $this->escolherMelhorCaixa($termo, $candidatos);
        $id = (int) ($melhor['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $caixa = $this->buscarCaixaPorId($id);

        return $this->caixaValida($caixa) ? $caixa : null;
    }

    /**
     * Lista todas as caixas de Governador Valadares com coordenadas (mapa de calor).
     *
     * @return array{caixas: array<int, array<string, mixed>>, total_ids: int, total_com_coordenadas: int}
     */
    public function listarCaixasGovernadorValadares(): array
    {
        $idPasta = (int) config('services.geogrid.pasta_caixas', 0);
        if ($idPasta <= 0) {
            return ['caixas' => [], 'total_ids' => 0, 'total_com_coordenadas' => 0];
        }

        $ttl = (int) config('services.geogrid.caixas_cache_minutes', 360);

        return Cache::remember(
            "geogrid_caixas_gv_mapa_{$idPasta}",
            now()->addMinutes($ttl),
            function () {
                $ids = $this->listarIdsCaixasGrupo();
                $caixas = [];
                $lote = max(10, min(100, (int) config('services.geogrid.caixas_mapa_lote', 60)));

                foreach (array_chunk($ids, $lote) as $loteIds) {
                    foreach ($this->obterMapaItens($loteIds) as $registro) {
                        $caixa = $this->normalizarCaixaLeve($registro);
                        if ($caixa !== null) {
                            $caixas[] = $caixa;
                        }
                    }
                }

                return [
                    'caixas' => $caixas,
                    'total_ids' => count($ids),
                    'total_com_coordenadas' => count($caixas),
                ];
            }
        );
    }

    /** @return array<int, int> */
    private function listarIdsCaixasGrupo(): array
    {
        $ids = [];

        foreach ($this->listarCaixasDoGrupo() as $item) {
            if (($item['item'] ?? '') !== 'caixa') {
                continue;
            }

            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $registro
     * @return array<string, mixed>|null
     */
    private function normalizarCaixaLeve(array $registro): ?array
    {
        $dados = is_array($registro['dados'] ?? null) ? $registro['dados'] : [];
        $lat = $dados['latitude'] ?? null;
        $lng = $dados['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        $id = (int) ($dados['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return [
            'id' => $id,
            'sigla' => (string) ($dados['sigla'] ?? ''),
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'status' => (string) ($dados['status'] ?? ''),
        ];
    }

    /**
     * Carrega caixas do grupo principal (ex.: Caixas de emenda).
     *
     * @return array<int, array<string, mixed>>
     */
    private function listarCaixasDoGrupo(): array
    {
        $idPasta = (int) config('services.geogrid.pasta_caixas', 0);
        if ($idPasta <= 0) {
            return [];
        }

        $ttl = (int) config('services.geogrid.caixas_cache_minutes', 360);

        return Cache::remember(
            "geogrid_caixas_grupo_{$idPasta}",
            now()->addMinutes($ttl),
            function () use ($idPasta) {
                try {
                    return $this->buscarItensMenuMapa([
                        'grupos' => [
                            ['idPasta' => $idPasta, 'item' => 'caixa'],
                        ],
                    ]);
                } catch (RuntimeException) {
                    return [];
                }
            }
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function montarConsultasBusca(string $termo, string $termoBusca): array
    {
        $idPasta = (int) config('services.geogrid.pasta_caixas', 0);
        $grupo = $idPasta > 0
            ? ['grupos' => [['idPasta' => $idPasta, 'item' => 'caixa']]]
            : [];

        $base = array_merge([
            'subPastas' => 'S',
            'recipientes' => 'S',
            'modoProjeto' => ['N'],
            'itens' => ['caixa'],
        ], $grupo);

        $consultas = [
            array_merge($base, ['pesquisa' => '%' . $termo . '%']),
            array_merge($base, ['pesquisaPorEquipamento' => '%' . $termo . '%']),
        ];

        if ($termoBusca !== '' && $termoBusca !== mb_strtolower($termo)) {
            $consultas[] = array_merge($base, ['pesquisa' => '%' . $termoBusca . '%']);
        }

        if (preg_match('/p?\s*(\d+)/i', $termo, $matches)) {
            $numero = $matches[1];
            $consultas[] = array_merge($base, ['pesquisa' => '%' . $numero . '%']);
            $consultas[] = array_merge($base, ['pesquisaPorEquipamento' => '%' . $numero . '%']);
        }

        return $consultas;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidatos
     * @return array<string, mixed>
     */
    private function escolherMelhorCaixa(string $termo, array $candidatos): array
    {
        $termoNorm = $this->normalizarTermoBusca($termo);
        $melhor = $candidatos[0];
        $melhorScore = -1;

        foreach ($candidatos as $candidato) {
            $score = $this->pontuarCaixa($termo, $candidato);
            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhor = $candidato;
            }
        }

        return $melhor;
    }

    /** @param  array<string, mixed>  $candidato */
    private function pontuarCaixa(string $termo, array $candidato): int
    {
        $termoNorm = $this->normalizarTermoBusca($termo);
        $siglaBruta = (string) ($candidato['sigla'] ?? $candidato['nome'] ?? '');
        $sigla = $this->normalizarTermoBusca($siglaBruta);
        $nome = $this->normalizarTermoBusca((string) ($candidato['nome'] ?? ''));

        if (strcasecmp(trim($siglaBruta), trim($termo)) === 0) {
            return 2000;
        }

        if ($sigla === $termoNorm || $nome === $termoNorm) {
            return 1000;
        }

        if (str_contains($sigla, $termoNorm) || str_contains($nome, $termoNorm)) {
            return 500 + strlen($termoNorm);
        }

        if (preg_match('/(\d+)/', $termoNorm, $termoNum) && preg_match('/(\d+)/', $sigla, $siglaNum)) {
            if ($termoNum[1] === $siglaNum[1]) {
                return 400;
            }
        }

        return 1;
    }

    private function normalizarTermoBusca(string $valor): string
    {
        $valor = mb_strtolower(trim($valor));
        $valor = preg_replace('/^caixa[\s\-_]*/i', '', $valor) ?? $valor;
        $valor = preg_replace('/[\s\-_]+/', '', $valor) ?? $valor;

        return $valor;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $mapa
     * @param  array<string, mixed>|null  $ficha
     * @return array<string, mixed>
     */
    private function normalizarCaixa(array $item, array $mapa, ?array $ficha): array
    {
        $dados = is_array($mapa['dados'] ?? null) ? $mapa['dados'] : [];
        $dadosFicha = is_array($ficha['dados'] ?? null) ? $ficha['dados'] : [];
        $posteMapa = is_array($mapa['poste'] ?? null) ? $mapa['poste'] : [];
        $posteFicha = is_array($ficha['poste'] ?? null) ? $ficha['poste'] : [];
        $cidade = is_array($ficha['cidade'] ?? null) ? $ficha['cidade'] : [];

        $latitude = $dados['latitude'] ?? $dadosFicha['latitude'] ?? null;
        $longitude = $dados['longitude'] ?? $dadosFicha['longitude'] ?? null;

        return [
            'id' => (int) ($dados['id'] ?? $item['id'] ?? 0),
            'sigla' => (string) ($dados['sigla'] ?? $item['sigla'] ?? $item['nome'] ?? ''),
            'item' => (string) ($dados['item'] ?? 'caixa'),
            'status' => (string) ($dados['status'] ?? $item['status'] ?? ''),
            'latitude' => $latitude !== null ? (float) $latitude : null,
            'longitude' => $longitude !== null ? (float) $longitude : null,
            'id_pasta' => $mapa['idPasta'] ?? null,
            'poste' => [
                'id' => $posteMapa['id'] ?? $posteFicha['id'] ?? null,
                'item' => $posteMapa['item'] ?? $posteFicha['item'] ?? null,
            ],
            'endereco' => $this->extrairEndereco($ficha, $cidade),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $ficha
     * @param  array<string, mixed>  $cidade
     * @return array<string, mixed>|null
     */
    private function extrairEndereco(?array $ficha, array $cidade): ?array
    {
        if ($ficha === null) {
            return null;
        }

        $poste = is_array($ficha['poste'] ?? null) ? $ficha['poste'] : [];
        $dadosPoste = is_array($poste['dados'] ?? null) ? $poste['dados'] : $poste;

        $endereco = [
            'logradouro' => $dadosPoste['endereco'] ?? $dadosPoste['logradouro'] ?? $ficha['endereco'] ?? null,
            'numero' => $dadosPoste['numero'] ?? null,
            'bairro' => $dadosPoste['bairro'] ?? null,
            'cep' => $dadosPoste['cep'] ?? null,
            'complemento' => $dadosPoste['complemento'] ?? null,
            'cidade' => $cidade['nome'] ?? $cidade['descricao'] ?? null,
            'estado' => $cidade['uf'] ?? $cidade['estado'] ?? null,
        ];

        $endereco = array_filter($endereco, fn ($valor) => $valor !== null && $valor !== '');

        return $endereco === [] ? null : $endereco;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function requestApi(string $method, string $path, array $query = []): Response
    {
        $url = $this->apiUrl() . $path;
        $client = $this->client();

        $response = $method === 'get'
            ? $client->get($url, $query)
            : $client->{$method}($url, $query);

        if ($response->status() === 401) {
            $client = $this->client(true);
            $response = $method === 'get'
                ? $client->get($url, $query)
                : $client->{$method}($url, $query);
        }

        return $response;
    }
}
