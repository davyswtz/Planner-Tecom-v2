#!/usr/bin/env bash
# Hostinger compartilhada — executar na raiz do Laravel após upload/Git pull.
set -euo pipefail

echo "==> Planner Hostinger — pós-deploy"

if [[ ! -f .env ]]; then
  echo "ERRO: .env não encontrado. Copie deploy/env/hostinger.env.example para .env"
  exit 1
fi

php artisan down --retry=60 2>/dev/null || true

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
  composer dump-autoload -o --no-interaction
elif [[ -f composer.phar ]]; then
  php composer.phar install --no-dev --optimize-autoloader --no-interaction
  php composer.phar dump-autoload -o --no-interaction
else
  echo "AVISO: composer não encontrado — use o ZIP gerado com deploy/build-hostinger-pack.ps1 (já inclui vendor)"
fi

php artisan optimize:clear
php artisan hostinger:setup --force

php artisan up 2>/dev/null || true

echo ""
echo "==> Concluído."
