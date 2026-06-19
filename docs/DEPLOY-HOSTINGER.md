# Deploy na Hostinger — Planner Telecom

Guia para **hospedagem compartilhada Hostinger** (domínio + MySQL, **sem VPS**).

Nesse plano **não dá** para rodar Laravel Reverb (`php artisan reverb:start`). O tempo real entre usuários usa **Pusher** (WebSocket na nuvem, plano gratuito suficiente para uso interno).

---

## Resumo do que você precisa

| Item | Onde configurar |
|------|-----------------|
| Código PHP | Upload ou Git no servidor |
| Banco MySQL | hPanel → Bancos de dados (ou importar `localhost.sql`) |
| Domínio / HTTPS | hPanel → Domínios |
| Tempo real | Conta gratuita em [dashboard.pusher.com](https://dashboard.pusher.com/) |
| `.env` | Modelo: `deploy/env/hostinger.env.example` |

---

## 1. Pusher (tempo real)

1. Crie conta em [Pusher](https://pusher.com/) → **Channels** → **Create app**.
2. Nome: `Planner` (ou similar).
3. Cluster: **sa1** (São Paulo).
4. Anote: **app_id**, **key**, **secret**, **cluster**.

No `.env` de produção:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=1234567
PUSHER_APP_KEY=sua-key
PUSHER_APP_SECRET=seu-secret
PUSHER_APP_CLUSTER=sa1
```

**Nunca** use `BROADCAST_CONNECTION=log` ou `reverb` na Hostinger compartilhada.

---

## 2. Estrutura de pastas no servidor

A raiz web deve ser a pasta **`public/`** do Laravel.

### Opção A — hPanel permite apontar document root (recomendado)

```
/home/u123456789/domains/planner.seudominio.com.br/
├── app/
├── bootstrap/
├── config/
├── ...
└── public/          ← document root aponta aqui
```

### Opção B — só `public_html` (comum na Hostinger)

Coloque o projeto **acima** de `public_html` e o conteúdo de `public/` dentro de `public_html`:

```
/home/u123456789/
├── planner/              ← raiz do Laravel (app, vendor, .env…)
└── domains/.../public_html/
    ├── index.php         ← de public/index.php
    ├── .htaccess
    └── js/ …
```

Edite `public_html/index.php` — ajuste os paths:

```php
require __DIR__.'/../planner/vendor/autoload.php';
$app = require_once __DIR__.'/../planner/bootstrap/app.php';
```

(Ajuste `../planner` para o caminho real da sua instalação.)

---

## 3. Arquivo `.env`

```bash
cp deploy/env/hostinger.env.example .env
```

Preencha:

- `APP_KEY` — gere com `php artisan key:generate` (SSH) ou copie de ambiente seguro
- `APP_URL` — URL HTTPS do site (ex.: `https://planner.tecom.com.br`)
- `DB_*` — credenciais do MySQL no hPanel
- `PUSHER_*` — dados do passo 1

Webhooks do Google Chat vêm do banco (`app_config`), não do `.env`.

---

## 4. Banco de dados

Se já existe dump da produção:

1. hPanel → **phpMyAdmin** → importar `localhost.sql`
2. Ou via SSH: `mysql -u USUARIO -p BANCO < localhost.sql`

Depois:

```bash
php artisan db:schema-check   # opcional — conferência
php artisan migrate --force   # cria personal_access_tokens etc.
```

Detalhes das tabelas: seção 8 em `docs/DOCUMENTACAO.md`.

---

## 5. Pós-deploy

Com **SSH** (hPanel → Avançado → SSH):

```bash
cd ~/planner   # ou caminho da raiz do Laravel
chmod +x deploy/hostinger-post-deploy.sh
./deploy/hostinger-post-deploy.sh
```

Sem SSH, execute manualmente (Terminal do hPanel ou cron one-shot):

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permissões:

```bash
chmod -R ug+rwx storage bootstrap/cache
```

---

## 6. Teste final

1. Acesse o site, faça **login**.
2. Abra **duas abas** em `/rompimento`.
3. No console do navegador (F12):  
   `[Planner] Tempo real ativo (pusher) — canal planner.tasks`
4. Crie uma tarefa em uma aba → a outra atualiza **sem F5**.

---

## Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` com HTTPS correto
- [ ] `BROADCAST_CONNECTION=pusher` + keys Pusher preenchidas
- [ ] `php artisan config:cache` após alterar `.env`
- [ ] Document root = pasta `public/`
- [ ] `storage/` e `bootstrap/cache/` graváveis
- [ ] Duas abas sincronizam

---

## Problemas comuns

| Sintoma | Solução |
|---------|---------|
| Página 500 | Ver `storage/logs/laravel.log`; conferir `APP_KEY` e permissões |
| Tempo real não aparece no console | `BROADCAST_CONNECTION` errado ou Pusher vazio — rode `config:cache` |
| Erro Pusher / connection failed | Cluster errado (use `sa1`) ou key/secret trocados |
| Erro 401 em `/broadcasting/auth` | Faça login de novo (token Sanctum) |
| API 401 nas telas | Token expirado — login em `/login` |

---

## Desenvolvimento local vs produção

| Ambiente | `BROADCAST_CONNECTION` |
|----------|--------------------------|
| Seu PC (com `reverb:start`) | `reverb` |
| Hostinger compartilhada | **`pusher`** |

---

## Arquivos úteis

```
deploy/
├── hostinger-post-deploy.sh
└── env/
    └── hostinger.env.example    ← use este na Hostinger

deploy/supervisor/ e deploy/nginx/  → só para VPS (não se aplica ao seu plano)
```

---

*Última atualização: junho/2026*
