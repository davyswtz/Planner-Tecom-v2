<?php

namespace App\Services;

class CoordenadasChatFormatter
{
    /**
     * Formata coordenadas como link clicável no Google Chat.
     * Suporta uma ou várias coordenadas separadas por " | ".
     */
    public static function formatar(?string $coordenadas): string
    {
        $texto = trim((string) $coordenadas);
        if ($texto === '') {
            return '—';
        }

        if (str_contains($texto, ' | ')) {
            $partes = array_map(
                fn (string $parte) => self::formatarPar(trim($parte)),
                explode(' | ', $texto)
            );

            return implode(' | ', $partes);
        }

        return self::formatarPar($texto);
    }

    /**
     * Formata coordenadas entre parênteses (ex.: listas de etiquetas).
     */
    public static function formatarEntreParenteses(?string $coordenadas): string
    {
        $texto = trim((string) $coordenadas);
        if ($texto === '') {
            return '';
        }

        return '(' . self::formatarPar($texto) . ')';
    }

    public static function urlGoogleMaps(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps?q=' . rawurlencode("{$lat},{$lng}");
    }

    /**
     * Converte links no formato Google Chat (&lt;url|rótulo&gt;) para Markdown [rótulo](url),
     * usado pelo Nicon (exibe só as coordenadas, clicáveis).
     */
    public static function adaptarLinksParaNicon(string $texto): string
    {
        return (string) preg_replace_callback(
            '/<((?:https?:\/\/)[^|>]+)\|([^>]+)>/',
            static fn (array $m): string => '[' . $m[2] . '](' . $m[1] . ')',
            $texto
        );
    }

    private static function formatarPar(string $texto): string
    {
        if ($texto === '' || $texto === '—') {
            return $texto;
        }

        $pares = TrocaEtiquetaParser::extrairCoordenadas($texto);
        if ($pares === null) {
            return $texto;
        }

        [$lat, $lng] = $pares;
        $url = self::urlGoogleMaps($lat, $lng);

        return "<{$url}|{$texto}>";
    }
}
