# Recarrega o PATH e sobe o servidor Laravel (util quando o terminal do Cursor restaura sessao antiga)
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP nao encontrado. Instale com: winget install PHP.PHP.8.3"
    exit 1
}

php artisan serve @args
