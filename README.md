# Planner Telecom v2

Sistema de **gestão operacional** para equipes de campo de telecom. Centraliza o acompanhamento de demandas (rompimentos, manutenções, correções de sinal, etc.) em **Kanban**, com **Ordens de Serviço (OS)**, agenda de técnicos, anexos e notificações em tempo real para as equipes (Google Chat, Nicon e Telegram).

> Produção de referência: [chutepremiadoplanner.chutepremiado.com](https://chutepremiadoplanner.chutepremiado.com)

---

## Sumário

- [Propósito](#propósito)
- [Dores que o sistema resolve](#dores-que-o-sistema-resolve)
- [Vantagens](#vantagens)
- [Módulos e funcionalidades](#módulos-e-funcionalidades)
- [Stack tecnológica](#stack-tecnológica)
- [Arquitetura](#arquitetura)
- [Integrações](#integrações)
- [Pré-requisitos](#pré-requisitos)
- [Como rodar localmente](#como-rodar-localmente)
- [Como rodar com Docker](#como-rodar-com-docker)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Testes úteis (Telegram / Nicon)](#testes-úteis-telegram--nicon)
- [Deploy](#deploy)
- [Estrutura do repositório](#estrutura-do-repositório)
- [Documentação adicional](#documentação-adicional)

---

## Propósito

O **Planner Telecom** nasceu para organizar o trabalho diário das operações de rede: do chamado até a finalização em campo.

Em vez de espalhar status em planilhas, grupos de WhatsApp e sistemas isolados, o Planner oferece:

1. **Um quadro Kanban por tipo de demanda** (rompimento, troca de poste, correção de sinal, etc.)
2. **Ordens de Serviço vinculadas** à tarefa pai, com técnicos, anexos e histórico
3. **Notificações automáticas** nos canais oficiais da operação quando o status muda
4. **Consulta a inventário/sinal** (Nicon / GeoGrid) sem sair do fluxo operacional
5. **Agenda operacional** para encaixar OS na escala dos técnicos

O núcleo do domínio é a entidade `OpTask`: toda demanda (pai ou OS filha) vive na mesma tabela, diferenciada por `categoria` e `parent_task_id`.

---

## Dores que o sistema resolve

| Dor no dia a dia | Como o Planner ataca |
|------------------|----------------------|
| Status de campo “perdido” em conversas e planilhas | Kanban com colunas de status e filtros por região/técnico |
| Equipe de chat não sabe quando uma OS avançou | Notificação automática (Google Chat / Nicon / Telegram) no arraste de card |
| Falta de rastreio entre demanda e OS filhas | Hierarquia pai → OS, com sequência e detalhe por card |
| Coordenadas / endereço difíceis de comunicar | Formatação de coordenadas clicáveis + reverse geocoding |
| Anexos de campo (fotos) não chegam ao time de suporte | Upload de anexos na OS + espelho no chat (imagem no comentário/tópico) |
| Escala de técnicos desorganizada | Módulo de agenda com OS disponíveis, duração e indisponibilidades |
| Consulta de caixa/sinal em outro sistema | Telas e APIs integradas ao Nicon / GeoGrid |
| Ambientes diferentes (Goval, Vale do Aço, Teste) | Webhooks e chats **por região** |

---

## Vantagens

- **Operação visual**: o time enxerga o funil de demandas no Kanban, não só em lista
- **Multi-região**: Goval, Vale do Aço, Teste (e aliases) com canais e webhooks separados
- **Notificação multi-canal**: Google Chat (webhooks), Nicon Chat e Telegram em paralelo — falha em um não bloqueia os outros
- **Tempo quase real no board**: long polling (`/api/planner/changes/wait`) + broadcast (Reverb local / Pusher em hospedagem compartilhada)
- **Histórico e anexos** por tarefa/OS
- **Permissões de usuário** e gestão de técnicos
- **Templates de mensagem** editáveis (tela de mensagens)
- **Deploy flexível**: local, Docker ou Hostinger (compartilhada / VPS)
- **Código orientado a serviços** (`app/Services/*`) — regras de negócio fora dos controllers

---

## Módulos e funcionalidades

| Rota | Módulo |
|------|--------|
| `/login` | Autenticação |
| `/dashboard` | Visão geral e métricas |
| `/rompimento` | Kanban de rompimentos |
| `/troca-de-poste` | Troca de poste |
| `/troca-de-etiqueta` | Troca de etiqueta |
| `/otimizacao-de-rede` | Otimização de rede |
| `/atendimento` | Atendimento |
| `/correcao-de-sinal` | Correção de sinal |
| `/manutencao-corretiva` | Manutenção corretiva |
| `/certificacao-cemig` | Certificação CEMIG |
| `/ordem-de-servico` | Visão de OS / dashboard / heatmap |
| `/tarefas` | Tarefas gerais |
| `/agenda` | Agenda operacional de técnicos |
| `/buscar-caixa` | Busca de caixa (Nicon) |
| `/correcao-de-dados` | Correção de dados |
| `/mensagens` | Templates de notificação |
| `/usuarios` | Usuários e permissões |
| `/configuracoes` | Configurações |

### Fluxo típico

```
Criar demanda (ex.: rompimento)
        │
        ▼
Card no Kanban (status: Criada)
        │  arraste / edição
        ▼
Notificação no chat da região
        │
        ▼
Criar OS filha(s) + anexar fotos
        │
        ▼
Comentário / tópico no chat + atualização no board
        │
        ▼
Finalizar → reação / status concluído
```

---

## Stack tecnológica

| Camada | Tecnologia |
|--------|------------|
| Backend | **PHP 8.3** + **Laravel 13** |
| API | REST + **Laravel Sanctum** (Bearer token) |
| Frontend | **Blade** + JavaScript modular (`public/js/planner-*.js`) |
| Build front | **Vite 8** + **Tailwind CSS 4** |
| Banco | **MySQL** |
| Tempo real | **Laravel Reverb** (local/VPS) ou **Pusher** (Hostinger compartilhada) |
| Long polling | Endpoint próprio `/api/planner/changes/wait` |
| Planilhas | PhpSpreadsheet |
| Containers | Docker + nginx (PHP-FPM) |
| Qualidade | PHPUnit, Laravel Pint |

### Linguagens e formatos

- **PHP** — API, services, comandos Artisan, migrations
- **JavaScript** — Kanban, modais de OS, polling, realtime, anexos
- **HTML / Blade** — páginas e layout
- **CSS / Tailwind** — estilos
- **SQL** — schema MySQL (migrations Laravel)
- **JSON** — payloads de API e configs (ex.: `TELEGRAM_CHAT_IDS`)
- **Bash / PowerShell** — scripts de deploy (`deploy/`)

---

## Arquitetura

```
┌──────────────────────────────────────────────────────────────┐
│  Browser (Blade + public/js)                                 │
│  Kanban · Agenda · OS · Anexos · Long polling                │
└────────────────────────────┬─────────────────────────────────┘
                             │ Authorization: Bearer <token>
                             ▼
┌──────────────────────────────────────────────────────────────┐
│  Laravel API (/api/*) — auth:sanctum                         │
│  Controllers → Services → Eloquent Models                    │
└───────────────┬──────────────────────────────┬───────────────┘
                │                              │
                ▼                              ▼
         MySQL (op_tasks,              Google Chat webhooks
         usuario, tecnicos,            Nicon API + Chat
         anexos, agenda…)              Telegram Bot API
                                       GeoGrid
```

### Entidade central: `OpTask`

| Campo relevante | Uso |
|-----------------|-----|
| `taskCode` | Código único (`GV-ROM-001`, `VA-OS-423`, …) |
| `categoria` | Tipo da demanda |
| `status` | Coluna do Kanban / estado da OS |
| `regiao` | Roteia webhook e chat |
| `parent_task_id` | Liga OS filha à tarefa pai |
| `chat_thread_key` | Thread Google Chat |
| `telegram_message_id` / `telegram_topic_id` | Post e discussão no Telegram |
| `nicon_*` | IDs de mensagem/tópico no Nicon (quando migrado) |

---

## Integrações

| Sistema | Função |
|---------|--------|
| **Google Chat** | Webhooks por região; thread por tarefa |
| **Nicon** | API operacional + chat paralelo (tópicos/replies) |
| **Telegram** | Canal + grupo de discussão (post pai + comentários/OS/anexos) |
| **GeoGrid** | Consulta de caixas / inventário |
| **Nominatim (OSM)** | Reverse geocoding de coordenadas |
| **Pusher / Reverb** | Broadcast de atualizações entre usuários |

As notificações de chat passam por `GoogleChatService::enviarNotificacao()`, que dispara **Nicon** e **Telegram** em paralelo ao webhook do Google.

---

## Pré-requisitos

### Desenvolvimento local

- PHP **8.3+** (extensões: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`, `zip`)
- Composer 2
- Node.js **20+** e npm
- MySQL 8 (ou MariaDB compatível)
- Git

### Docker (opcional)

- Docker Engine + Docker Compose

---

## Como rodar localmente

### 1. Clonar o repositório

```bash
git clone https://github.com/davyswtz/Planner-Tecom-v2.git
cd Planner-Tecom-v2
```

### 2. Instalar dependências PHP e Node

```bash
composer install
npm install
```

### 3. Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com o banco local:

```env
APP_NAME=Planner
APP_URL=http://127.0.0.1:8000
APP_LOCALE=pt_BR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=planner
DB_USERNAME=root
DB_PASSWORD=sua_senha

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync
```

> Em Windows, se webhooks/HTTPS falharem com cURL error 60, use `HTTP_VERIFY_SSL=false` no `.env` **somente em local**.

### 4. Banco de dados

Crie o database e rode as migrations:

```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate
# opcional: php artisan db:schema-check
```

Se você tiver dump legado (`localhost.sql`), importe antes e depois rode `migrate` + `db:schema-check`.

### 5. Assets front-end

```bash
# desenvolvimento (hot reload)
npm run dev

# ou build de produção
npm run build
```

### 6. Subir a aplicação

**Opção A — tudo junto (recomendado no dia a dia):**

```bash
composer run dev
```

Isso sobe, em paralelo:

- `php artisan serve`
- `php artisan reverb:start`
- `php artisan queue:listen`
- `php artisan pail` (logs)
- `npm run dev` (Vite)

**Opção B — manual:**

```bash
php artisan serve
# em outro terminal, se usar Reverb:
php artisan reverb:start
```

Acesse: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### 7. Login

Use um usuário existente na tabela `usuario` (autenticação própria + Sanctum).  
O token fica em `localStorage.planner_token` e as chamadas à API usam `Authorization: Bearer …`.

---

## Como rodar com Docker

Há um `docker-compose.yml` com **app (PHP-FPM)** + **nginx**.

```bash
cp deploy/env/docker.env.example .env
# ajuste APP_KEY, DB_* e credenciais das APIs
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"  # gere APP_KEY se necessário

docker compose build
docker compose up -d
```

Por padrão a aplicação sobe em **http://localhost:8080** (`APP_PORT`).

O MySQL pode ser:

- **Externo** (`DB_HOST=host.docker.internal`), ou
- **No Docker** (se o compose/profile `with-db` estiver habilitado no seu ambiente)

Volumes: `planner_storage` persiste `storage/app` (anexos).

---

## Variáveis de ambiente

Além do `.env.example`, use os modelos em `deploy/env/` conforme o destino.

### Essenciais

| Variável | Descrição |
|----------|-----------|
| `APP_KEY` | Chave Laravel (`php artisan key:generate`) |
| `APP_URL` / `PLANNER_PUBLIC_URL` | URL pública (anexos no chat) |
| `DB_*` | Conexão MySQL |
| `BROADCAST_CONNECTION` | `reverb`, `pusher` ou `log` |

### Nicon

```env
NICON_BASE_URL=https://nicon.ibitelecom.com.br
NICON_EMAIL=
NICON_PASSWORD=
NICON_CHAT_ENABLED=true
NICON_CHAT_CONVERSAS='{"Goval":4143,"Vale do Aço":4140,"Teste":4180}'
```

### GeoGrid

```env
GEOGRID_BASE_URL=
GEOGRID_USER=
GEOGRID_PASSWORD=
GEOGRID_PASTA_CAIXAS=1713
```

### Telegram

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ENABLED=true
TELEGRAM_CHAT_IDS='{"Goval":-100...,"Vale do Aço":-100...,"Teste":-100...}'
TELEGRAM_DISCUSSION_CHAT_IDS='{"Goval":-100...,"Vale do Aço":-100...,"Teste":-100...}'
```

### Reverb (local)

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=planner-local
REVERB_APP_KEY=planner-local-key
REVERB_APP_SECRET=planner-local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## Testes úteis (Telegram / Nicon)

Com o `.env` configurado:

```bash
# Telegram — mensagem simples no grupo Teste
php artisan telegram:testar-chat "olá" --regiao=Teste

# Telegram — simula arraste de card (mesmo fluxo do board)
php artisan telegram:testar-chat --simular-drag --regiao=Teste

# Telegram — post pai + OS no tópico de discussão
php artisan telegram:testar-chat --fluxo-thread --task=ID_DA_TAREFA_PAI --regiao=Teste

# Telegram — anexo no tópico
php artisan telegram:testar-chat --enviar-anexo=ID_ANEXO --task=ID_PAI
# ou fluxo real de OS:
php artisan telegram:testar-chat --enviar-anexo=ID_ANEXO --os=ID_DA_OS

# Nicon
php artisan nicon:testar-chat
```

Logs: `storage/logs/laravel.log` (procure por `Telegram canal:` / `Nicon chat:`).

---

## Deploy

### Hostinger (hospedagem compartilhada)

Guia rápido: [`deploy/INSTALAR-HOSTINGER.md`](deploy/INSTALAR-HOSTINGER.md)  
Detalhes: [`docs/DEPLOY-HOSTINGER.md`](docs/DEPLOY-HOSTINGER.md)

Resumo:

1. Gerar pacote: `.\deploy\build-hostinger-pack.ps1`
2. Enviar `planner/` + conteúdo de `public_html/`
3. Ajustar `.env` (`APP_URL`, `DB_PASSWORD`, Pusher se usar tempo real)
4. No terminal do hPanel: `php artisan hostinger:setup`

> Em compartilhado, use **`BROADCAST_CONNECTION=pusher`** (Reverb precisa de processo persistente / VPS).

### Docker no servidor

```bash
docker compose build && docker compose up -d
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Estrutura do repositório

```
Planner-Tecom-v2/
├── app/
│   ├── Console/Commands/     # telegram:*, nicon:*, hostinger:setup, db:schema-check…
│   ├── Http/Controllers/Api/ # REST
│   ├── Models/               # OpTask, anexos, técnicos, agenda…
│   └── Services/             # Regras de negócio + Nicon/Telegram/GeoGrid
├── config/                   # services.php (nicon, telegram, geogrid…)
├── database/migrations/
├── deploy/                   # scripts e .env de produção
├── docs/                     # documentação estendida
├── public/js/                # planner-kanban.js, planner-polling.js…
├── resources/views/          # Blade por módulo
├── routes/api.php | web.php
├── docker-compose.yml
└── Dockerfile
```

---

## Documentação adicional

| Arquivo | Conteúdo |
|---------|----------|
| [`docs/DOCUMENTACAO.md`](docs/DOCUMENTACAO.md) | Arquitetura, regras de negócio e API |
| [`docs/DEPLOY-HOSTINGER.md`](docs/DEPLOY-HOSTINGER.md) | Deploy detalhado na Hostinger |
| [`deploy/INSTALAR-HOSTINGER.md`](deploy/INSTALAR-HOSTINGER.md) | Deploy rápido (ZIP → setup) |
| [`deploy/env/*.env.example`](deploy/env/) | Modelos de `.env` por ambiente |

---

## Scripts Composer / NPM

```bash
composer run setup   # install + .env + key + migrate + npm build
composer run dev     # serve + reverb + queue + pail + vite
composer run test    # PHPUnit
npm run dev          # Vite em modo desenvolvimento
npm run build        # assets de produção
```

---

## Licença

Projeto interno operacional (**Planner Telecom / Tecom**).  
O esqueleto Laravel original é open-source sob a [licença MIT](https://opensource.org/licenses/MIT).

---

## Contribuindo

1. Crie uma branch a partir de `main`
2. Mantenha regras de negócio nos **Services**
3. Teste notificações no grupo **Teste** antes de produção
4. Abra PR descrevendo o impacto operacional (Kanban, OS, chat, agenda)

Dúvidas de domínio → consulte `docs/DOCUMENTACAO.md` e os services em `app/Services/`.
