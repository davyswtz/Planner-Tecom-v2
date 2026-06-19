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
elif [[ -f composer.phar ]]; then
  php composer.phar install --no-dev --optimize-autoloader --no-interaction
else
  echo "AVISO: composer não encontrado — instale dependências manualmente"
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up 2>/dev/null || true

echo ""
echo "==> Concluído."
echo "    Confira no .env: BROADCAST_CONNECTION=pusher + PUSHER_APP_* preenchidos"
echo "    Teste: duas abas em /rompimento — console deve mostrar tempo real (pusher)"
