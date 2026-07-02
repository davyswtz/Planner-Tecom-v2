<?php

namespace App\Services;

class TrocaEtiquetaParser
{
  /** @return string[] */
    public static function parseTitulo(?string $titulo): array
    {
        $partes = preg_split('/[,;]+/', (string) $titulo) ?: [];

        return array_values(array_filter(array_map(
            fn (string $parte) => self::normalizarNome($parte),
            $partes
        )));
    }

    public static function normalizarNome(string $valor): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $valor) ?? '');

        return $texto === '' ? '' : mb_strtoupper($texto);
    }

    /**
     * @return array<int, array{nome: string, coordenadas: string, endereco: string}>
     */
    public static function parseItens(
        ?string $titulo,
        ?string $descricao,
        ?string $coordenadasFallback = '',
        ?string $enderecoFallback = '',
    ): array {
        $nomes = self::parseTitulo($titulo);
        $mapa = [];

        $blocos = preg_split('/\n\s*\n/', trim((string) $descricao)) ?: [];
        foreach ($blocos as $bloco) {
            $nome = '';
            $coordenadas = '';
            $endereco = '';

            foreach (preg_split('/\r\n|\r|\n/', trim($bloco)) ?: [] as $linha) {
                $texto = trim($linha);
                if (str_starts_with($texto, 'Etiqueta:')) {
                    $nome = self::normalizarNome(substr($texto, 9));
                } elseif (str_starts_with($texto, 'Coordenadas:')) {
                    $coordenadas = trim(substr($texto, 12));
                } elseif (preg_match('/^Endere[cç]o:\s*/iu', $texto)) {
                    $endereco = trim(preg_replace('/^Endere[cç]o:\s*/iu', '', $texto) ?? '');
                }
            }

            if ($nome !== '') {
                $mapa[$nome] = self::criarItem($nome, $coordenadas, $endereco);
            }
        }

        if ($nomes === [] && $mapa !== []) {
            return array_values($mapa);
        }

        $itens = array_map(
            fn (string $nome) => $mapa[$nome] ?? self::criarItem($nome),
            $nomes
        );

        if ($mapa === [] && ($coordenadasFallback !== '' || $enderecoFallback !== '')) {
            $coords = array_map('trim', explode(' | ', (string) $coordenadasFallback));
            $enderecos = array_map('trim', explode(' | ', (string) $enderecoFallback));

            foreach ($itens as $index => $item) {
                if ($item['coordenadas'] === '' && isset($coords[$index]) && $coords[$index] !== '') {
                    $itens[$index]['coordenadas'] = $coords[$index];
                }
                if ($item['endereco'] === '' && isset($enderecos[$index]) && $enderecos[$index] !== '') {
                    $itens[$index]['endereco'] = $enderecos[$index];
                }
            }

            if (count($itens) === 1) {
                if ($itens[0]['coordenadas'] === '' && trim((string) $coordenadasFallback) !== '') {
                    $itens[0]['coordenadas'] = trim((string) $coordenadasFallback);
                }
                if ($itens[0]['endereco'] === '' && trim((string) $enderecoFallback) !== '') {
                    $itens[0]['endereco'] = trim((string) $enderecoFallback);
                }
            }
        }

        return $itens;
    }

    /**
     * Itens parseados e ordenados por proximidade geográfica (vizinho mais próximo).
     *
     * @return array<int, array{nome: string, coordenadas: string, endereco: string}>
     */
    public static function parseItensParaMensagem(
        ?string $titulo,
        ?string $descricao,
        ?string $coordenadasFallback = '',
        ?string $enderecoFallback = '',
    ): array {
        return self::ordenarPorProximidade(
            self::parseItens($titulo, $descricao, $coordenadasFallback, $enderecoFallback)
        );
    }

    /**
     * Ordena etiquetas: começa pela primeira com coordenadas e segue sempre para a mais próxima.
     * Etiquetas sem coordenadas permanecem ao final, na ordem original.
     *
     * @param  array<int, array{nome: string, coordenadas: string, endereco: string}>  $itens
     * @return array<int, array{nome: string, coordenadas: string, endereco: string}>
     */
    public static function ordenarPorProximidade(array $itens): array
    {
        if (count($itens) <= 1) {
            return $itens;
        }

        $comCoordenadas = [];
        $semCoordenadas = [];

        foreach ($itens as $item) {
            $coords = self::extrairCoordenadas($item['coordenadas']);
            if ($coords !== null) {
                $comCoordenadas[] = ['item' => $item, 'coords' => $coords];
            } else {
                $semCoordenadas[] = $item;
            }
        }

        if (count($comCoordenadas) <= 1) {
            return $itens;
        }

        $indiceInicial = self::indiceParMaisProximo($comCoordenadas);
        $atual = $comCoordenadas[$indiceInicial];
        $ordenados = [$atual['item']];
        unset($comCoordenadas[$indiceInicial]);
        $comCoordenadas = array_values($comCoordenadas);
        $latAtual = $atual['coords'][0];
        $lngAtual = $atual['coords'][1];

        while ($comCoordenadas !== []) {
            $indiceMaisProximo = 0;
            $menorDistancia = PHP_FLOAT_MAX;

            foreach ($comCoordenadas as $indice => $candidato) {
                $distancia = self::distanciaMetros(
                    $latAtual,
                    $lngAtual,
                    $candidato['coords'][0],
                    $candidato['coords'][1]
                );

                if ($distancia < $menorDistancia) {
                    $menorDistancia = $distancia;
                    $indiceMaisProximo = $indice;
                }
            }

            $proximo = $comCoordenadas[$indiceMaisProximo];
            unset($comCoordenadas[$indiceMaisProximo]);
            $comCoordenadas = array_values($comCoordenadas);

            $ordenados[] = $proximo['item'];
            $latAtual = $proximo['coords'][0];
            $lngAtual = $proximo['coords'][1];
        }

        return array_merge($ordenados, $semCoordenadas);
    }

    /**
     * @param  array<int, array{item: array{nome: string, coordenadas: string, endereco: string}, coords: array{0: float, 1: float}}>  $comCoordenadas
     */
    private static function indiceParMaisProximo(array $comCoordenadas): int
    {
        $melhorIndice = 0;
        $menorDistancia = PHP_FLOAT_MAX;

        foreach ($comCoordenadas as $i => $origem) {
            foreach ($comCoordenadas as $j => $destino) {
                if ($i >= $j) {
                    continue;
                }

                $distancia = self::distanciaMetros(
                    $origem['coords'][0],
                    $origem['coords'][1],
                    $destino['coords'][0],
                    $destino['coords'][1]
                );

                if ($distancia < $menorDistancia) {
                    $menorDistancia = $distancia;
                    $melhorIndice = $i;
                }
            }
        }

        return $melhorIndice;
    }

    /**
     * @return array{0: float, 1: float}|null [latitude, longitude]
     */
    public static function extrairCoordenadas(?string $coordenadas): ?array
    {
        $texto = trim((string) $coordenadas);
        if ($texto === '') {
            return null;
        }

        if (! preg_match('/(-?\d+(?:[.,]\d+)?)\s*[,;]\s*(-?\d+(?:[.,]\d+)?)/', $texto, $matches)) {
            return null;
        }

        $lat = (float) str_replace(',', '.', $matches[1]);
        $lng = (float) str_replace(',', '.', $matches[2]);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [$lat, $lng];
    }

    private static function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerra = 6371000;
        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $raioTerra * $c;
    }

    /**
     * @param  array<int, array{nome: string, coordenadas: string, endereco: string}>  $itens
     */
    public static function montarDescricao(array $itens): string
    {
        $blocos = [];

        foreach ($itens as $item) {
            $linhas = ['Etiqueta: ' . $item['nome']];
            if (trim($item['coordenadas']) !== '') {
                $linhas[] = 'Coordenadas: ' . trim($item['coordenadas']);
            }
            if (trim($item['endereco']) !== '') {
                $linhas[] = 'Endereço: ' . trim($item['endereco']);
            }
            $blocos[] = implode("\n", $linhas);
        }

        return implode("\n\n", $blocos);
    }

    /**
     * @param  array<int, array{nome: string, coordenadas: string, endereco: string}>  $itens
     */
    public static function formatarNomes(array $itens): string
    {
        $nomes = array_map(fn (array $item) => $item['nome'], $itens);

        return $nomes !== [] ? implode(', ', $nomes) : '—';
    }

    /**
     * Nome da etiqueta seguido da localização (endereço e coordenadas).
     *
     * @param  array<int, array{nome: string, coordenadas: string, endereco: string}>  $itens
     */
    public static function formatarLocalizacaoLista(array $itens): string
    {
        if ($itens === []) {
            return '—';
        }

        $linhas = [];
        foreach ($itens as $indice => $item) {
            $detalhes = [];
            $endereco = trim($item['endereco']);
            $coordenadas = trim($item['coordenadas']);

            if ($endereco !== '') {
                $detalhes[] = $endereco;
            }
            if ($coordenadas !== '') {
                $detalhes[] = "({$coordenadas})";
            }

            $localizacao = $detalhes !== [] ? implode(' ', $detalhes) : '—';
            $ordem = $indice + 1;
            $linhas[] = "{$ordem}. 📍 *{$item['nome']}:* {$localizacao}";
        }

        return implode("\n", $linhas);
    }

    /**
     * @param  array<int, array{nome: string, coordenadas: string, endereco: string}>  $itens
     */
    public static function formatarCoordenadasLista(array $itens): string
    {
        if ($itens === []) {
            return '—';
        }

        $linhas = [];
        foreach ($itens as $indice => $item) {
            $coordenadas = trim($item['coordenadas']);
            $ordem = $indice + 1;
            $linhas[] = $coordenadas !== ''
                ? "{$ordem}. 📍 *{$item['nome']}:* {$coordenadas}"
                : "{$ordem}. 📍 *{$item['nome']}:* —";
        }

        return implode("\n", $linhas);
    }

    /** @return array{nome: string, coordenadas: string, endereco: string} */
    private static function criarItem(string $nome, string $coordenadas = '', string $endereco = ''): array
    {
        return [
            'nome' => $nome,
            'coordenadas' => trim($coordenadas),
            'endereco' => trim($endereco),
        ];
    }
}
