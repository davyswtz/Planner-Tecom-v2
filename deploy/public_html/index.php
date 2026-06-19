<?php

/**
 * Hostinger — ponto de entrada quando o Laravel fica FORA de public_html.
 *
 * Estrutura esperada:
 *   /home/usuario/planner/          ← raiz Laravel (app, vendor, .env…)
 *   /home/usuario/domains/.../public_html/  ← este arquivo + js/ + .htaccess
 *
 * Se a pasta planner estiver ao lado de public_html, o caminho padrão funciona.
 * Caso contrário, ajuste $laravelRoot abaixo.
 */
$laravelRoot = __DIR__.'/../planner';

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    $laravelRoot = __DIR__.'/../../planner';
}

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    echo 'Planner: pasta Laravel não encontrada. Ajuste $laravelRoot em public_html/index.php';
    exit(1);
}

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
