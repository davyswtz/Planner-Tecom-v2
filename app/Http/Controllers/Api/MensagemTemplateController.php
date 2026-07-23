<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoordenadasChatFormatter;
use App\Services\MensagemTemplateService;
use App\Services\TecnicoChatMencaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MensagemTemplateController extends Controller
{
    public function __construct(
        private MensagemTemplateService $mensagens,
        private TecnicoChatMencaoService $mencoes,
    ) {
    }

    public function show()
    {
        return response()->json([
            'grupos' => $this->mensagens->grupos(),
            'catalogo' => $this->mensagens->catalogo(),
            'placeholders' => $this->mensagens->placeholders(),
            'templates' => $this->mensagens->listarEfetivos(),
            'customizados' => $this->mensagens->listarSalvos(),
        ]);
    }

    public function update(Request $request)
    {
        $categorias = array_keys(config('mensagens.categorias', []));

        $request->validate([
            'templates' => ['required', 'array'],
            'templates.*' => ['array'],
            'templates.*.*' => ['nullable', 'string', 'max:8000'],
        ]);

        $entrada = $request->input('templates', []);
        $filtrado = [];

        foreach ($categorias as $categoria) {
            if (! isset($entrada[$categoria]) || ! is_array($entrada[$categoria])) {
                continue;
            }

            $filtrado[$categoria] = $entrada[$categoria];
        }

        $this->mensagens->salvar($filtrado);

        return response()->json([
            'message' => 'Mensagens salvas com sucesso.',
            'templates' => $this->mensagens->listarEfetivos(),
            'customizados' => $this->mensagens->listarSalvos(),
        ]);
    }

    public function preview(Request $request)
    {
        $categorias = array_keys(config('mensagens.categorias', []));
        $statuses = collect(config('mensagens.categorias', []))
            ->flatMap(fn (array $meta) => $meta['statuses'] ?? [])
            ->unique()
            ->values()
            ->all();

        $request->validate([
            'categoria' => ['required', 'string', Rule::in($categorias)],
            'status' => ['required', 'string', Rule::in($statuses)],
            'template' => ['nullable', 'string', 'max:8000'],
        ]);

        $categoria = $request->string('categoria')->toString();
        $status = $request->string('status')->toString();
        $template = trim($request->input('template', ''));

        if ($template === '') {
            $template = $this->mensagens->obterTemplate($categoria, $status) ?? '';
        }

        $exemplo = $this->mensagens->dadosExemplo($categoria);

        $texto = $this->mensagens->renderizar(
            $template,
            $exemplo,
            'Criada',
            $status,
            'usuario.exemplo'
        );

        // Mesma adaptação do envio ao Telegram (menções + HTML).
        $textoTelegram = CoordenadasChatFormatter::adaptarTextoParaTelegram(
            $this->mencoes->adaptarTextoParaTelegram(
                $this->mencoes->adaptarTextoParaNicon($texto)
            )
        );

        return response()->json([
            'texto' => $texto,
            'texto_telegram' => $textoTelegram,
        ]);
    }
}
