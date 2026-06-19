# Deploy rápido — Hostinger

Guia mínimo: **gerar ZIP → enviar → preencher `.env` → um comando**.

---

## 1. Gerar o pacote (no seu PC)

```powershell
cd C:\Projetos\Planner-Tecom-v2
.\deploy\build-hostinger-pack.ps1
```

Isso cria **`dist/planner-hostinger.zip`** com:
- pasta `planner/` — Laravel completo **com vendor**
- pasta `public_html/` — arquivos do site (js, index.php, .htaccess)
- `.env` com `APP_KEY` já gerada — você só preenche URL e senha do banco

---

## 2. Subdomínio no hPanel

1. **Domínios → Subdomínios → Criar** (ex.: `planner.seudominio.com.br`)
2. Anote o caminho da pasta `public_html` do subdomínio

---

## 3. Enviar arquivos

No **Gerenciador de arquivos** ou FTP:

| Origem no ZIP | Destino no servidor |
|---------------|---------------------|
| `planner/` | `/home/SEU_USUARIO/planner/` |
| conteúdo de `public_html/` | `/home/SEU_USUARIO/domains/planner.seudominio.com.br/public_html/` |

> A pasta `planner` fica **fora** de `public_html`. O `index.php` em `public_html` aponta para ela automaticamente.

---

## 4. Editar `.env` (só 2 campos obrigatórios)

Arquivo: `/home/SEU_USUARIO/planner/.env`

```env
APP_URL=https://planner.seudominio.com.br
DB_PASSWORD="sua_senha"
```

O resto já vem configurado para seu banco:

```env
DB_HOST=localhost
DB_DATABASE=samu6922_burrinhosProjetosProd
DB_USERNAME=samu6922_davypandrade
```

---

## 5. Finalizar (Terminal do hPanel)

```bash
cd ~/planner
php artisan hostinger:setup
```

Esse comando:
- testa conexão com o banco
- roda migrations pendentes
- gera caches de config/rota/view

Se der **erro 500**, ajuste permissões:

```bash
chmod -R ug+rwx storage bootstrap/cache
```

---

## 6. SSL

hPanel → **SSL** → ativar Let's Encrypt para o subdomínio.

Teste: `https://planner.seudominio.com.br/up` → deve retornar OK.

---

## Tempo real entre abas (opcional)

Por padrão o pacote usa `BROADCAST_CONNECTION=log` (app funciona, mas abas não sincronizam sozinhas).

Para tempo real na Hostinger compartilhada:

1. Crie app em [dashboard.pusher.com](https://dashboard.pusher.com/) (cluster **sa1**)
2. No `.env`:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=sa1
```

3. `php artisan config:cache`

---

## Estrutura no servidor

```
/home/usuario/
├── planner/                 ← Laravel (.env, vendor, app…)
│   ├── app/
│   ├── vendor/
│   └── .env
└── domains/
    └── planner.seudominio.com.br/
        └── public_html/       ← index.php, js/, .htaccess
            ├── index.php      → aponta para ../../planner
            └── js/
```

---

## Problemas comuns

| Problema | Solução |
|----------|---------|
| Página em branco / 500 | `storage/logs/laravel.log`; permissões em `storage/` |
| "Laravel não encontrado" | Confirme que `planner/` está no caminho esperado; ajuste `$laravelRoot` em `public_html/index.php` |
| Erro de banco | No servidor use `DB_HOST=localhost`, não o IP remoto |
| Login não funciona | Banco já tem tabela `usuario` — use credenciais existentes |

---

*Guia completo: `docs/DEPLOY-HOSTINGER.md`*
