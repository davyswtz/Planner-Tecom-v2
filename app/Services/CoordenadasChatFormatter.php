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

    /**
     * Adapta texto dos templates (estilo Google/markdown leve) para Telegram HTML:
     * - &lt;url|rótulo&gt; → &lt;a href="url"&gt;rótulo&lt;/a&gt; (coords clicáveis)
     * - §§TGUSER{id}|{nome}§§ → menção por ID (tg://user?id=)
     * - *negrito* → &lt;b&gt; · _itálico_ → &lt;i&gt; · ~tachado~ → &lt;s&gt; · `código` → &lt;code&gt;
     * - @username Telegram permanece intacto (notifica)
     */
    public static function adaptarTextoParaTelegram(string $texto): string
    {
        $links = [];

        // Menções por ID (vindas do TecnicoChatMencaoService).
        $texto = (string) preg_replace_callback(
            '/§§TGUSER(\d+)\|([^§]+)§§/',
            static function (array $m) use (&$links): string {
                $chave = '§§TG'.count($links).'§§';
                $links[$chave] = TecnicoChatMencaoService::htmlMencaoPorId((int) $m[1], $m[2]);

                return $chave;
            },
            $texto
        );

        // Protege @username do Telegram (evita _itálico_ quebrar e garante entity "mention").
        $texto = (string) preg_replace_callback(
            '/(^|[^\\w])@([A-Za-z][A-Za-z0-9_]{3,31})\\b/',
            static function (array $m) use (&$links): string {
                $chave = '§§TG'.count($links).'§§';
                $links[$chave] = '@'.$m[2];

                return $m[1].$chave;
            },
            $texto
        );

        $texto = (string) preg_replace_callback(
            '/<((?:https?:\/\/)[^|>]+)\|([^>]+)>/',
            static function (array $m) use (&$links): string {
                $chave = '§§TG'.count($links).'§§';
                $url = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $rotulo = htmlspecialchars($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $links[$chave] = '<a href="'.$url.'">'.$rotulo.'</a>';

                return $chave;
            },
            $texto
        );

        $texto = htmlspecialchars($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = (string) preg_replace('/\*([^*\n]+)\*/u', '<b>$1</b>', $texto);
        $texto = (string) preg_replace('/_([^_\n]+)_/u', '<i>$1</i>', $texto);
        $texto = (string) preg_replace('/~([^~\n]+)~/u', '<s>$1</s>', $texto);
        $texto = (string) preg_replace('/`([^`\n]+)`/u', '<code>$1</code>', $texto);

        return str_replace(array_keys($links), array_values($links), $texto);
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
