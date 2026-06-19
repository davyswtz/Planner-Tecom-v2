# Gera pacote pronto para upload na Hostinger (com vendor incluido).
# Uso: .\deploy\build-hostinger-pack.ps1
# Saida: dist/planner-hostinger.zip

$ErrorActionPreference = 'Stop'

$root = Split-Path $PSScriptRoot -Parent
$dist = Join-Path $root 'dist'
$staging = Join-Path $dist 'hostinger-pack'
$zipPath = Join-Path $dist 'planner-hostinger.zip'

function Find-Php {
    $env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' +
                [System.Environment]::GetEnvironmentVariable('Path', 'User')
    $wingetPhp = "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    if (Test-Path $wingetPhp) { return $wingetPhp }
    if (Get-Command php -ErrorAction SilentlyContinue) {
        $cmd = (Get-Command php).Source
        if ($cmd -like '*.bat') {
            $real = $cmd -replace '\\bin\\php\.bat$', '\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
            if (Test-Path $real) { return $real }
        }
        return $cmd
    }
    throw 'PHP nao encontrado. Instale com: winget install PHP.PHP.8.3'
}

function Invoke-ComposerInstall {
    param([string]$Php, [string]$ProjectRoot)
    $composerPhar = "$env:LOCALAPPDATA\Composer\composer.phar"
    Push-Location $ProjectRoot
    if (Test-Path $composerPhar) {
        & $Php $composerPhar install --no-dev --optimize-autoloader --no-interaction
    } elseif (Get-Command composer.cmd -ErrorAction SilentlyContinue) {
        composer.cmd install --no-dev --optimize-autoloader --no-interaction
    } elseif (Get-Command composer -ErrorAction SilentlyContinue) {
        composer install --no-dev --optimize-autoloader --no-interaction
    } else {
        Pop-Location
        throw 'Composer nao encontrado.'
    }
    if ($LASTEXITCODE -ne 0) {
        Pop-Location
        throw "composer install falhou (codigo $LASTEXITCODE)"
    }
    Pop-Location
}

$php = Find-Php
Write-Host "==> PHP: $php"
Write-Host "==> Instalando dependencias (composer --no-dev)..."
Push-Location $root
Invoke-ComposerInstall -Php $php -ProjectRoot $root
Pop-Location

Write-Host "==> Montando pacote..."
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
$plannerDir = Join-Path $staging 'planner'
$publicDir = Join-Path $staging 'public_html'
New-Item -ItemType Directory -Force -Path $plannerDir, $publicDir | Out-Null

$exclude = @('.git', 'node_modules', 'dist', '.cursor', 'tests', 'docker', '.env', '.env.local', 'serve.ps1')

Get-ChildItem $root -Force | Where-Object { $_.Name -notin $exclude } | ForEach-Object {
    Copy-Item $_.FullName (Join-Path $plannerDir $_.Name) -Recurse -Force
}

Copy-Item (Join-Path $root 'public\*') $publicDir -Recurse -Force
Copy-Item (Join-Path $root 'deploy\public_html\index.php') (Join-Path $publicDir 'index.php') -Force
Copy-Item (Join-Path $root 'deploy\public_html\.htaccess') (Join-Path $publicDir '.htaccess') -Force

$envTemplate = Get-Content (Join-Path $root 'deploy\env\hostinger.env.example') -Raw
Push-Location $plannerDir
$key = (& $php artisan key:generate --show 2>$null).Trim()
Pop-Location
if ($key) {
    $envTemplate = $envTemplate -replace '(?m)^APP_KEY=.*', "APP_KEY=$key"
}
Set-Content (Join-Path $plannerDir '.env') $envTemplate.TrimEnd() -Encoding UTF8

$readme = @'
PLANNER — PACOTE HOSTINGER
==========================

1. Envie a pasta "planner" para /home/SEU_USUARIO/planner

2. Envie o CONTEUDO de "public_html" para a public_html do subdominio

3. Edite planner/.env:
   APP_URL=https://seu-subdominio.seudominio.com.br
   DB_PASSWORD=sua_senha

4. Terminal do hPanel:
   cd ~/planner
   php artisan hostinger:setup

5. Se erro 500: chmod -R ug+rwx storage bootstrap/cache

Detalhes: deploy/INSTALAR-HOSTINGER.md
'@
Set-Content (Join-Path $staging 'LEIA-ME.txt') $readme -Encoding UTF8

Write-Host "==> Compactando..."
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
New-Item -ItemType Directory -Force -Path $dist | Out-Null
Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $zipPath -Force
Remove-Item $staging -Recurse -Force

Write-Host ""
Write-Host "Pronto: $zipPath"
