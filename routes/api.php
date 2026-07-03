<?php

use App\Http\Controllers\Api\AtendimentoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificacaoCemigController;
use App\Http\Controllers\Api\CorrecaoDadosController;
use App\Http\Controllers\Api\CorrecaoDeSinalController;
use App\Http\Controllers\Api\GeoGridController;
use App\Http\Controllers\Api\MensagemTemplateController;
use App\Http\Controllers\Api\NiconController;
use App\Http\Controllers\Api\NotificacaoController;
use App\Http\Controllers\Api\OpTaskController;
use App\Http\Controllers\Api\OrdemServicoController;
use App\Http\Controllers\Api\OtimizacaoDeRedeController;
use App\Http\Controllers\Api\PlannerChangesController;
use App\Http\Controllers\Api\RompimentoController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\TrocaEtiquetaController;
use App\Http\Controllers\Api\TrocaPosteController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\WebhookConfigController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::post("login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::post("logout", [AuthController::class, "logout"]);
    Route::get("me", [AuthController::class, "me"]);
    Route::get("notificacoes", [NotificacaoController::class, "index"]);
    Route::post("notificacoes/ler-todas", [
        NotificacaoController::class,
        "marcarTodasLidas",
    ]);
    Route::post("notificacoes/{id}/ler", [
        NotificacaoController::class,
        "marcarLida",
    ])->whereNumber("id");
    Route::apiResource("op-tasks", OpTaskController::class);
    Route::apiResource("rompimentos", RompimentoController::class);
    Route::get("rompimentos/{id}/os", [
        RompimentoController::class,
        "listarOS",
    ]);
    Route::apiResource("tecnicos", TecnicoController::class);
    Route::put("os/{id}", [OpTaskController::class, "update"]);
    Route::get("troca-poste/coordenada", [
        TrocaPosteController::class,
        "buscarEndereco",
    ]);
    Route::get("troca-poste/{id}/os", [
        TrocaPosteController::class,
        "listarOS",
    ]);
    Route::apiResource("troca-poste", TrocaPosteController::class);
    Route::get("troca-etiqueta/coordenada", [
        TrocaEtiquetaController::class,
        "buscarEndereco",
    ]);
    Route::get("troca-etiqueta/{id}/os", [
        TrocaEtiquetaController::class,
        "listarOS",
    ]);
    Route::apiResource(
        "troca-etiqueta",
        TrocaEtiquetaController::class,
    )->parameters([
        "troca-etiqueta" => "troca_etiqueta",
    ]);
    Route::get("otimizacao-rede/coordenada", [
        OtimizacaoDeRedeController::class,
        "buscarEndereco",
    ]);
    Route::get("otimizacao-rede/{id}/os", [
        OtimizacaoDeRedeController::class,
        "listarOS",
    ]);
    Route::apiResource("otimizacao-rede", OtimizacaoDeRedeController::class);
    Route::get("atendimento/coordenada", [
        AtendimentoController::class,
        "buscarEndereco",
    ]);
    Route::get("atendimento/{id}/os", [
        AtendimentoController::class,
        "listarOS",
    ]);
    Route::apiResource("atendimento", AtendimentoController::class);
    Route::get("correcao-sinal/coordenada", [
        CorrecaoDeSinalController::class,
        "buscarEndereco",
    ]);
    Route::post("correcao-sinal/from-caixa", [
        CorrecaoDeSinalController::class,
        "storeFromCaixa",
    ]);
    Route::get("correcao-sinal/{id}/os", [
        CorrecaoDeSinalController::class,
        "listarOS",
    ]);
    Route::apiResource("correcao-sinal", CorrecaoDeSinalController::class);
    Route::get("certificacao-cemig/{id}/os", [
        CertificacaoCemigController::class,
        "listarOS",
    ]);
    Route::apiResource(
        "certificacao-cemig",
        CertificacaoCemigController::class,
    );
    Route::get("ordem-servico/heatmap", [
        OrdemServicoController::class,
        "heatmap",
    ]);
    Route::get("ordem-servico/dashboard", [
        OrdemServicoController::class,
        "dashboard",
    ]);
    Route::get("ordem-servico/exportar", [
        OrdemServicoController::class,
        "exportar",
    ]);
    Route::get("ordem-servico/{id}", [
        OrdemServicoController::class,
        "show",
    ])->whereNumber("id");
    Route::get("ordem-servico", [OrdemServicoController::class, "index"]);
    Route::get("usuarios/opcoes", [UsuarioController::class, "opcoes"]);
    Route::post("nicon/sinal-caixa", [
        NiconController::class,
        "buscarSinalCaixa",
    ]);
    Route::post("nicon/sinal-atual-cliente", [
        NiconController::class,
        "buscarSinalAtualCliente",
    ]);
    Route::post("geogrid/caixa/buscar", [
        GeoGridController::class,
        "buscarCaixa",
    ]);
    Route::get("geogrid/caixas", [GeoGridController::class, "listarCaixas"]);
    Route::post("geogrid/itens/mapa", [GeoGridController::class, "mapaItens"]);
    Route::middleware("permissao:corrigir_dados")->group(function () {
        Route::get("correcao-dados", [CorrecaoDadosController::class, "index"]);
        Route::post("correcao-dados", [
            CorrecaoDadosController::class,
            "store",
        ]);
        Route::put("correcao-dados/{id}", [
            CorrecaoDadosController::class,
            "update",
        ])->whereNumber("id");
        Route::delete("correcao-dados/{id}", [
            CorrecaoDadosController::class,
            "destroy",
        ])->whereNumber("id");
    });

    Route::middleware("permissao:visualizar_aba_usuarios")->group(function () {
        Route::apiResource("usuarios", UsuarioController::class)->only([
            "index",
            "store",
            "update",
            "destroy",
        ]);
    });
    Route::get("planner/changes", [PlannerChangesController::class, "index"]);
    Route::get("planner/changes/wait", [
        PlannerChangesController::class,
        "wait",
    ]);

    Route::middleware("permissao:adicionar_webhook")->group(function () {
        Route::get("webhook-config", [WebhookConfigController::class, "show"]);
        Route::put("webhook-config", [
            WebhookConfigController::class,
            "update",
        ]);
        Route::post("webhook-config/{id}/testar", [
            WebhookConfigController::class,
            "testar",
        ])->whereNumber("id");
    });

    Route::middleware("permissao:visualizar_tela_mensagens")->group(
        function () {
            Route::get("mensagens-templates", [
                MensagemTemplateController::class,
                "show",
            ]);
            Route::put("mensagens-templates", [
                MensagemTemplateController::class,
                "update",
            ]);
            Route::post("mensagens-templates/preview", [
                MensagemTemplateController::class,
                "preview",
            ]);
        },
    );
});
