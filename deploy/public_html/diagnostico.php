<?php
/**
 * Diagnóstico rápido na Hostinger — NÃO deixe em produção após o teste.
 * Envie para public_html/diagnostico.php e acesse:
 * https://chutepremiadoplanner.chutepremiado.com/diagnostico.php
 * Apague o arquivo depois.
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== Planner — diagnóstico ===\n\n";
echo 'PHP: '.PHP_VERSION."\n";
echo 'Hora: '.date('c')."\n\n";

$roots = [
    __DIR__.'/../planner',
    __DIR__.'/../../planner',
];

$laravelRoot = null;
foreach ($roots as $root) {
    if (is_file($root.'/.env')) {
        $laravelRoot = $root;
        break;
    }
}

if ($laravelRoot === null) {
    echo "ERRO: pasta planner/.env não encontrada.\n";
    echo "Caminhos testados:\n";
    foreach ($roots as $root) {
        echo "  - {$root}\n";
    }
    exit(1);
}

echo "Laravel: {$laravelRoot}\n";

$env = [];
foreach (file($laravelRoot.'/.env', FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$required = ['APP_KEY', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
foreach ($required as $key) {
    $val = $env[$key] ?? '';
    if ($val === '') {
        echo "ERRO: {$key} vazio no .env\n";
    } else {
        $show = in_array($key, ['DB_PASSWORD', 'APP_KEY'], true)
            ? substr($val, 0, 8).'...'
            : $val;
        echo "OK {$key}={$show}\n";
    }
}

echo "\n--- Teste MySQL (5s timeout) ---\n";

$host = $env['DB_HOST'] ?? 'localhost';
$db = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';
$port = $env['DB_PORT'] ?? '3306';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $count = $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    echo "MySQL OK — usuarios na tabela: {$count}\n";
} catch (Throwable $e) {
    echo 'MySQL FALHOU: '.$e->getMessage()."\n";
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        echo "\nDICA: na Hostinger use DB_HOST=localhost (não IP público).\n";
    }
}

$cacheFile = $laravelRoot.'/bootstrap/cache/config.php';
echo "\n--- Cache ---\n";
echo is_file($cacheFile)
    ? "config.php em cache EXISTE — rode: php artisan config:clear\n"
    : "config.php em cache: não encontrado (ok)\n";

echo "\nApague este arquivo (diagnostico.php) após o teste.\n";
