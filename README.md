# Hyperf Bank API

API RESTful de uma plataforma de pagamentos simplificada (com 2 tipos de usuário — comum e lojista), carteira digital e transferência entre carteiras, com consulta a um **autorizador externo** e **notificação assíncrona** via mensageria.

Implementada em PHP 8.3 + Hyperf 3.x (Swoole) seguindo uma arquitetura modular em camadas (DDD / Ports & Adapters).

---

## Índice

- [Stack](#stack)
- [Arquitetura](#arquitetura)
- [Estrutura de diretórios](#estrutura-de-diretórios)
- [Como subir com Docker](#como-subir-com-docker)
- [Como subir sem Docker (local)](#como-subir-sem-docker-local)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Modelagem de dados](#modelagem-de-dados)
- [Endpoints da API](#endpoints-da-api)
- [Fluxo de transferência](#fluxo-de-transferência)
- [Regras de negócio](#regras-de-negócio)
- [Idempotência](#idempotência)
- [Serviços externos](#serviços-externos)
- [Testes](#testes)
- [Qualidade de código](#qualidade-de-código)
- [CI](#ci)
- [Decisões de projeto e divergências do enunciado](#decisões-de-projeto-e-divergências-do-enunciado)
- [Melhorias planejadas](#melhorias-planejadas)

---

## Stack

| Camada            | Tecnologia                                    |
|-------------------|-----------------------------------------------|
| Linguagem         | PHP 8.3 (também validado em 8.4 no CI/Docker) |
| Framework         | Hyperf 3.2 (Swoole — PHP assíncrono corrotina) |
| BD relacional     | MySQL 8.0                                     |
| Cache / lock      | Redis 7                                       |
| Mensageria        | RabbitMQ 3 (AMQP)                             |
| Cliente HTTP      | Guzzle (via hyperf/guzzle)                     |
| JWT               | firebase/php-jwt (HS256)                       |
| Contêineres       | Docker / docker-compose                        |
| Análise estática  | PHPStan 2, Easy Coding Standard (ECS)          |
| Testes            | PHPUnit + co-phpunit + Mockery                |

---

## Arquitetura

Monolito modular com inversão de dependência nas bordas de I/O. Inspirado em DDD e Hexagonal (Ports & Adapters). Cada módulo possui **3 camadas internas**:

- **Domain** — entidades, value objects, enums, exceções e contratos. **Zero dependência de Hyperf/Eloquent**.
- **Application** — Use Cases que orquestram o fluxo de negócio. Dependem apenas de interfaces definidas em `Domain/Contract` ou `Common/Infrastructure/Contract`.
- **Infrastructure** — implementações concretas (repositórios Eloquent, clientes HTTP, JWT, AMQP, controllers, requests, middleware, resources).

### Regra de dependência

```
        ┌──────────────────────────────────────────────────┐
        │                      Http                         │  (Controllers, FormRequests, Middleware, Resources)
        │  só depende de Application                        │
        └──────────────────────────────────────────────────┘
                              ▼ depende
        ┌──────────────────────────────────────────────────┐
        │                  Application                      │  (UseCases)
        │  depende de interfaces (Contracts) — NUNCA de     │
        │  Hyperf, Eloquent, Guzzle ou AMQP diretamente      │
        └──────────────────────────────────────────────────┘
                              ▼ usa
        ┌──────────────────────────────────────────────────┐
        │                    Domain                         │  (Entities, VOs, Enums, Exceptions, Contracts)
        │  puro PHP — sem nenhum import de framework        │
        └──────────────────────────────────────────────────┘
                              ▲ implementa
        ┌──────────────────────────────────────────────────┐
        │                Infrastructure                     │  (Repositories, Models, Clients, Consumers)
        │  implementa os contratos definidos em             │
        │  Domain/Common                                  │
        └──────────────────────────────────────────────────┘
```

### Módulos em `app/`

| Módulo        | Responsabilidade                                                                |
|---------------|----------------------------------------------------------------------------------|
| **Common**    | Contratos cross-module (`DatabaseManagerContract`), `AbstractController`, `AbstractResource`, ExceptionHandler global, listeners |
| **User**      | Entidade `User`, VOs (`Cpf`, `Cnpj`, `Email`, `Password`), `UserType`, repositório |
| **Auth**      | Registro, login, JWT (`AuthContract` ↔ `JwtAuthService`), middleware `JwtAuthMiddleware` |
| **Wallet**    | Entidade `Wallet`, `Money` VO, débito/crédito, repositório com `lockForUpdate` |
| **Transaction** | Use case `TransferUseCase`, autorizador (`AuthorizerContract`), notificador (`NotifierContract`), publisher AMQP, consumer AMQP |

### Ports & Adapters

Cada borda externa é abstraída por um contrato e implementada por um adapter conectável via `config/autoload/dependencies.php`:

| Port (contrato)                       | Adapter padrão (dev)   | Adapter (prod)         |
|---------------------------------------|------------------------|------------------------|
| `AuthContract`                        | `JwtAuthService`       | `JwtAuthService`       |
| `AuthorizerContract`                  | `StubAuthorizer`       | `GuzzleAuthorizer`     |
| `NotifierContract`                    | `GuzzleNotifier`       | `GuzzleNotifier`       |
| `TransferPublisherContract`           | `AmqpTransferPublisher`| `AmqpTransferPublisher`|

> **Nota:** em `APP_ENV=dev`, o autorizador é substituído por um `StubAuthorizer` que sempre autoriza (o mock real nega de forma aleatória, o que quebra testes determinísticos). Em `testing`/`production` o `GuzzleAuthorizer` real é usado.

---

## Estrutura de diretórios

```
app/
├── Common/
│   └── Infrastructure/
│       ├── Contract/DatabaseManagerContract.php
│       ├── DatabaseManager.php
│       ├── Model.php
│       └── Http/
│           ├── AbstractController.php
│           ├── AbstractResource.php
│           └── Middleware/IdempotencyMiddleware.php
│       └── Exception/
│           ├── AppExceptionHandler.php
│           └── DomainExceptionHandler.php
├── User/
│   ├── Domain/{Entity,ValueObject,Enum,Exception,Contract}
│   ├── Application/UseCases/CreateUserUseCase.php
│   └── Infrastructure/{Repository,Model}
├── Auth/
│   ├── Domain/Contract/AuthContract.php
│   ├── Application/UseCases/{RegisterUseCase,LoginUseCase}
│   └── Infrastructure/{Service/JwtAuthService, Http}
├── Wallet/
│   ├── Domain/{Entity/Wallet,ValueObject/Money,Contract,Exception}
│   └── Infrastructure/{Repository,Model}
└── Transaction/
    ├── Domain/{Entity,Enum,Exception,Contract}
    ├── Application/UseCases/TransferUseCase.php
    └── Infrastructure/
        ├── Repository
        ├── Service/{GuzzleAuthorizer,GuzzleNotifier,StubAuthorizer}
        ├── Amqp/{Publisher,Producer,Consumer}
        └── Http/{Controller,Request,Resource}

config/autoload/          # config do Hyperf (dependências, amqp, databases, redis, jwt, ...)
migrations/               # 3 migrations (users, wallets, transactions)
test/                     # testes unit + feature
docker-compose.yml        # ambiente de desenvolvimento
docker-compose.ci.yml     # ambiente de CI
Dockerfile                # imagem hyperf/hyperf:8.4-alpine-v3.22-swoole
.github/workflows/ci.yml  # pipeline de CI (build, lint, test)
```

---

## Como subir com Docker

### Pré-requisitos
- Docker 24+ e Docker Compose v2
- Portas livres: `9501` (app), `3306` (mysql), `6379` (redis), `5672`/`15672` (rabbitmq)

### Passos
```bash
# 1. Clone o repositório
git clone <seu-fork-url> hyperf-bank-api && cd hyperf-bank-api

# 2. Configure o .env
cp .env.example .env

# 3. Gere um segredo JWT forte e cole no .env na variável JWT_SECRET
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
#   ou, sem PHP local:
openssl rand -hex 32

# 4. Suba os contêineres (build da imagem + mysql + redis + rabbitmq)
docker compose up -d --build

# 5. Aplique as migrations dentro do contêiner app
docker compose exec app php bin/hyperf.php migrate --force

# 6. (opcional) Popule o banco com 2 usuários de exemplo + carteiras
docker compose exec app php bin/hyperf.php db:seed

# 7. Verifique se subiu
curl http://localhost:9501/health
# esperado: OK
```

A API ficará disponível em `http://localhost:9501`.
A interface de gerência do RabbitMQ em `http://localhost:15672` (usuário/senha default: `hyperf`/`secret`, configuráveis via `.env`).

Logs da aplicação em `runtime/logs/hyperf.log`. O modo `server:watch` reinicia automaticamente ao editar arquivos em `app/` ou `config/`.

### Parar
```bash
docker compose down            # remove contêineres
docker compose down -v          # remove também os volumes (apaga o banco!)
```

---

## Como subir sem Docker (local)

Requer PHP 8.3+ com extensões: `swoole`, `pdo_mysql`, `redis`, `openssl`, `json`, `pcntl`, `inotify` (opcional p/ watch).

```bash
composer install
cp .env.example .env
# ajuste DB_HOST, REDIS_HOST, RABBITMQ_HOST para apontar para instâncias locais
# gere JWT_SECRET conforme acima
php bin/hyperf.php migrate --force
php bin/hyperf.php start
```

> Para que o consumer AMQP processe notificações, o `php bin/hyperf.php start` sobe automaticamente os processos consumers declarados via `#[Consumer]`.

---

## Variáveis de ambiente

`.env` (exemplo em `.env.example`):

| Variável                 | Descrição                                            | Default                                  |
|--------------------------|------------------------------------------------------|------------------------------------------|
| `APP_NAME`               | Nome da aplicação                                    | `hyperf-bank-api`                        |
| `APP_ENV`                | `dev` usa `StubAuthorizer`; qualquer outro, real     | `dev`                                    |
| `APP_PORT`               | Porta exposta da app                                 | `9501`                                   |
| `DB_DRIVER`              | Driver do banco                                       | `mysql`                                  |
| `DB_HOST`                | Host do MySQL                                        | `mysql` (no docker)                      |
| `DB_PORT`                | Porta do MySQL                                       | `3306`                                   |
| `DB_DATABASE`            | Database                                             | `hyperf`                                 |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciais                                     | `hyperf` / `secret`                      |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_AUTH` | Conexão Redis                  | `redis` / `6379` / `secret`              |
| `RABBITMQ_HOST`           | Host RabbitMQ                                       | `rabbitmq`                               |
| `RABBITMQ_PORT`           | Porta AMQP                                          | `5672`                                   |
| `RABBITMQ_USER` / `RABBITMQ_PASSWORD` | Credenciais                              | `hyperf` / `secret`                      |
| `RABBITMQ_VHOST`          | Vhost                                               | `/`                                      |
| `JWT_SECRET`             | **Obrigatório em produção** — chave HS256            | `local-dev-secret-change-in-production` |
| `JWT_TTL`                 | TTL do token em segundos                            | `3600`                                   |

---

## Modelagem de dados

Diagrama textual do schema (3 tabelas):

```
users (uuid PK)
 ├─ full_name      varchar(255)
 ├─ cpf            varchar(14)  UNIQUE NULLABLE   # usuário comum
 ├─ cnpj           varchar(18)  UNIQUE NULLABLE   # lojista
 ├─ email          varchar(255) UNIQUE
 ├─ password       varchar(255)                      # hash bcrypt
 ├─ type           varchar(20)  DEFAULT 'NORMAL'      # NORMAL | SHOPKEEPER
 └─ created_at, updated_at  datetime(6)

wallets (uuid PK)
 ├─ user_id    uuid  UNIQUE  FK→users.id ON DELETE RESTRICT
 ├─ balance    BIGINT UNSIGNED  DEFAULT 0              # centavos
 └─ created_at, updated_at

transactions (uuid PK)
 ├─ payer_id   uuid  FK→users.id
 ├─ payee_id   uuid  FK→users.id
 ├─ value      BIGINT UNSIGNED                          # valor em centavos
 ├─ status     ENUM('pending','completed','failed') DEFAULT 'pending'
 ├─ created_at, updated_at
 └─ indexes(payer_id, payee_id, created_at)
```

Regras:
- `users` 1:1 `wallets` (criada automaticamente no registro — escrita na mesma transação)
- `transactions` referencia 2 usuários (payer e payee)
- Saldo e valor sempre em **centavos** (`BIGINT UNSIGNED`)

---

## Endpoints da API

Base URL: `http://localhost:9501/api/v1`

| Método | Path               | Auth | Descrição                                |
|--------|--------------------|------|-------------------------------------------|
| `GET`  | `/health`          | —    | Health check                              |
| `POST` | `/api/v1/register` | —    | Cadastra usuário comum ou lojista         |
| `POST` | `/api/v1/login`    | —    | Autentica e devolve JWT                   |
| `POST` | `/api/v1/transfer` | `Idempotency-Key` (opcional) | Realiza transferência |

### POST `/api/v1/register`

Cadastra usuário comum (com CPF) ou lojista (com CNPJ). A carteira é criada na mesma transação.

**Request**
```http
POST /api/v1/register
Content-Type: application/json

{
  "full_name": "João Silva",
  "cpf": "123.456.789-09",
  "cnpj": null,
  "email": "joao@example.com",
  "password": "senhaSegura123",
  "type": "NORMAL"
}
```

| Campo        | Regras                                                            |
|--------------|-------------------------------------------------------------------|
| `full_name`  | obrigatório, string, até 255 chars                                |
| `cpf`        | obrigatório sem `cnpj`; CPF válido (com dígito verificador)       |
| `cnpj`       | obrigatório sem `cpf`; CNPJ válido (com dígito verificador)       |
| `email`      | obrigatório, formato de e-mail, único                             |
| `password`   | obrigatório, mínimo 8 caracteres                                  |
| `type`       | obrigatório, em (`NORMAL`, `SHOPKEEPER`)                          |

**Response 201**
```json
{
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": "01234567-89ab-cdef-0123-456789abcdef",
      "full_name": "João Silva",
      "email": "joao@example.com",
      "type": "NORMAL",
      "cpf": "123.456.789-09",
      "cnpj": null
    }
  }
}
```

**Erros**
| Status | Causa                                            |
|--------|--------------------------------------------------|
| `422`  | Erro de validação (CPF/CNPJ inválido, etc)        |
| `409`  | Usuário com mesmo CPF/CNPJ/e-mail já cadastrado  |

### POST `/api/v1/login`

Autentica usuário por `document` (CPF ou CNPJ, em qualquer formato) + `password`.

**Request**
```http
POST /api/v1/login
Content-Type: application/json

{
  "document": "123.456.789-09",
  "password": "senhaSegura123"
}
```

**Response 200** — mesmo shape do `register`.

**Erros**
| Status | Causa                          |
|--------|--------------------------------|
| `404`  | Usuário não encontrado         |
| `401`  | Senha inválida                 |

### POST `/api/v1/transfer`

Realiza transferência de valor entre duas carteiras.

**Request**
```http
POST /api/v1/transfer
Idempotency-Key: <uuid-opcional>
Content-Type: application/json

{
  "value": 100.0,
  "payer": "01234567-89ab-cdef-0123-456789abcdef",
  "payee": "abcdef01-2345-6789-abcd-ef0123456789"
}
```

| Campo    | Regras                                                            |
|----------|-------------------------------------------------------------------|
| `payer`  | obrigatório, UUID do usuário que envia                            |
| `payee`  | obrigatório, UUID do destinatário, deve ser diferente do `payer`  |
| `value`  | obrigatório, numérico, > 0, máximo 99999999.99                    |

**Response 201**
```json
{
  "data": {
    "id": "abc0...", 
    "payer_id": "0123...",
    "payee_id": "4567...",
    "value": 100.0,
    "status": "completed",
    "created_at": "2026-01-01T12:34:56.000000Z"
  }
}
```

**Erros**
| Status | Causa                                                         |
|--------|---------------------------------------------------------------|
| `404`  | Payer ou payee não encontrado                                 |
| `409`  | Idempotency-Key em uso                                       |
| `422`  | Self-transfer / value inválido / payer é lojista / saldo insuficiente / autorizador negou / erro de validação |
| `503`  | Autorizador externo indisponível                              |

### Exemplos `curl`

```bash
# Health
curl http://localhost:9501/health

# Register (comum)
curl -X POST http://localhost:9501/api/v1/register \
  -H 'Content-Type: application/json' \
  -d '{"full_name":"João Silva","cpf":"123.456.789-09","cnpj":null,"email":"joao@example.com","password":"senhaSegura123","type":"NORMAL"}'

# Register (lojista)
curl -X POST http://localhost:9501/api/v1/register \
  -H 'Content-Type: application/json' \
  -d '{"full_name":"Mercado LTDA","cpf":null,"cnpj":"11.222.333/0001-81","email":"lojista@example.com","password":"senhaSegura123","type":"SHOPKEEPER"}'

# Login
TOKEN=$(curl -s -X POST http://localhost:9501/api/v1/login \
  -H 'Content-Type: application/json' \
  -d '{"document":"123.456.789-09","password":"senhaSegura123"}' \
  | jq -r .data.token)

# Transfer
curl -X POST http://localhost:9501/api/v1/transfer \
  -H 'Content-Type: application/json' \
  -d '{"value": 100.0, "payer": "<uuid-do-payer>", "payee": "<uuid-do-payee>"}'
```

---

## Fluxo de transferência

Sequência orquestrada por `app/Transaction/Application/UseCases/TransferUseCase.php`:

```
1. assertNotSelfTransfer           # payer != payee  → 422 SelfTransfer
2. userRepository.findById(payer)  # 404 se não existir
3. assertPayerCanTransfer          # payer NÃO é SHOPKEEPER → 422 UnauthorizedTransfer
4. userRepository.findById(payee)  # 404 se não existir
5. AuthorizerContract::authorize() # GET https://util.devi.tools/api/v2/authorize
                                   #   ANTES da Db::transaction
                                   #   negado → 422 / indisponível → 503
6. DatabaseManager::transaction(
     a. lockWalletsOrdered()        # lockForUpdate na ordem lexicográfica dos UUIDs
                                   #   evita deadlock entre transferências concorrentes
     b. payerWallet.debit(money)    # InsufficientBalance → 422
     c. payeeWallet.credit(money)
     d. walletRepository.save(payer)
     e. walletRepository.save(payee)
     f. transaction.markAsCompleted()
     g. transactionRepository.save(transaction)
   )
7. publisher.publishTransferCompleted(transaction_id, payee_id)   # fila AMQP "transfer_notification"
8. return transaction 201
```

### Notificação (assíncrona via AMQP)

```
[UseCase] --publica--> transfer.completed (exchange "transfer", routing key "transfer.completed")
                                   │
                                   ▼
                    [TransferNotificationConsumer]
                                   │
                                   ▼
                       UserRepository.findById(payee_id)
                                   │
                                   ▼
                NotifierContract::notify(user)  →  POST https://util.devi.tools/api/v1/notify
                                   │
                          sucesso: ACK
                          falha:   NACK (requeue)
                          payee ausente: DROP
```

> Use RabbitMQ ao invés de `Swoole\Coroutine::create()` garante durabilidade e retry — se a app reiniciar ou o serviço de notificação caír, a mensagem não é perdida. Ver [Decisões de projeto](#decisões-de-projeto-e-divergências-do-enunciado).

---

## Regras de negócio

| #  | Regra                                                                  | Onde é aplicada                                            |
|----|------------------------------------------------------------------------|------------------------------------------------------------|
| 1  | Lojista (`SHOPKEEPER`) **não envia**, apenas recebe                    | `TransferUseCase::assertPayerCanTransfer` (l.113)         |
| 2  | Saldo insuficiente é rejeitado                                        | `Wallet::debit` → `InsufficientBalanceException`          |
| 3  | Autorizador externo consultado ANTES de abrir a transação             | `TransferUseCase:61` → `:63`                               |
| 4  | Transferência atômica (débito + crédito + persistência)               | `DatabaseManager::transaction` (l.63-77)                  |
| 5  | Notificação disparada APÓS o commit                                   | `publisher.publishTransferCompleted` (l.84)               |
| 6  | `Money` sempre em **centavos** (`BIGINT UNSIGNED`), jamais float       | `Money` VO + colunas em `migrations/`                      |
| 7  | Self-transfer bloqueado (FormRequest + UseCase — defesa em profundidade) | `TransferRequest::rules` (closure) + `TransferUseCase::assertNotSelfTransfer` (l.106) |
| 8  | CPF/CNPJ/e-mail únicos                                                | Constraints UNIQUE em `users` + check em `CreateUserUseCase` |
| 9  | Validação de dígitos CPF/CNPJ (algoritmo oficial)                      | VOs `Cpf` e `Cnpj`                                          |

### Mapeamento de exceções de domínio → HTTP

Definido em `app/Common/Infrastructure/Exception/DomainExceptionHandler.php`:

| Exceção                          | HTTP |
|----------------------------------|------|
| `UserNotFoundException`          | 404  |
| `InvalidCredentialsException`    | 401  |
| `UserAlreadyExistsException`     | 409  |
| `SelfTransferException`          | 422  |
| `UnauthorizedTransferException`  | 422  |
| `NotAuthorizedException`         | 422  |
| `InsufficientBalanceException`   | 422  |
| `AuthorizerUnavailableException` | 503  |
| `ValidationException`            | 422  |
| Qualquer outra                   | 500  |

---

## Idempotência

O middleware `IdempotencyMiddleware` (registrado no `/api/v1/transfer`) permite que clientes informem um header `Idempotency-Key` para evitar duplicações acidentais.

- Sem header: comportamento normal.
- Com header enviado pela primeira vez: processa normalmente e armazena a resposta 2xx no Redis por 24h.
- Repetição com mesmo header: devolve a **mesma resposta** com `X--idempotent-Replay: true`.
- Header em processamento por outra requisição concorrente: **409** `Idempotency-Key already in progress`.
- Resposta 4xx/5xx: o cache não é persistido (somente 2xx).

Chave Redis: `idempotency:<key>`, TTL = 86400s.

---

## Serviços externos

Mocks (https://util.devi.tools) fornecidos pelo enunciado:

| Serviço      | Método | URL                                          |
|--------------|--------|----------------------------------------------|
| Autorizador  | `GET`  | `https://util.devi.tools/api/v2/authorize`  |
| Notificador  | `POST` | `https://util.devi.tools/api/v1/notify`      |

Implementados em:
- `app/Transaction/Infrastructure/Service/GuzzleAuthorizer.php`
- `app/Transaction/Infrastructure/Service/GuzzleNotifier.php`

---

## Testes

```bash
# Unit tests (rápidos, isolados, sem BD)
composer test:unit
# ou dentro do container:
docker compose exec app composer test:unit

# Integration tests (requerem MySQL/Redis/RabbitMQ up — use docker compose)
docker compose exec app vendor/bin/co-phpunit -c phpunit.integration.xml

# Tudo (default no CI)
composer test
```

Estrutura (`test/`):

```
test/
├── Unit/         # VOs (Cpf, Cnpj, Email, Password, Money), User, UserType,
│                 # TransferUseCase (11 cenários), CreateUserUseCase, RegisterUseCase
└── Feature/      # endpoints /register, /login, /transfer (regras, auth, autorização)
    └── Support/  # FakeAuthorizer (authorize/deny/unavailable), InMemoryTransferPublisher
```

~130 métodos de teste (unit + feature); a suíte unit sozinha executa **176 casos** (com data providers). Usa _doubles_ (`FakeAuthorizer`, `InMemoryTransferPublisher`, mocks PHPUnit para `LoggerInterface`) isolando os adaptadores externos dos testes.

---

## Qualidade de código

| Ferramenta        | Comando              | Config                                              |
|-------------------|----------------------|-----------------------------------------------------|
| Easy Coding Standard | `composer ecs:check` | `ecs.php` — PSR-12 + regras Symplify             |
| PHPStan           | `composer analyse`   | `phpstan.neon.dist` — **nível 6** sobre `app/`      |

---

## CI

`.github/workflows/ci.yml` dispara em `/pull_request` e `push` em `main`/`develop`. Três jobs:

1. **build** — sobe `docker-compose.ci.yml`, espera `/health` responder.
2. **lint** — `composer install` + `vendor/bin/ecs check` + `composer analyse` (PHPStan nível 6) (PHP 8.4 + Swoole).
3. **test** _(needs: build, lint)_ — sobe services (MySQL, Redis, RabbitMQ), roda `migrate`, `composer test:unit`, `co-phpunit -c phpunit.integration.xml`.

---

## Decisões de projeto e divergências do enunciado

O enunciado PicPay Simplificado propõe um contrato mínimo. Algumas decisões foram tomadas para deixar a solução mais robusta/segura, e estão documentadas aqui para transparência na entrevista:

1. **Versionamento de API `/api/v1`** — boa prática REST; facilita evolução sem quebrar clientes. O enunciado não pede versão mas também não proíbe.

2. **Tipos `NORMAL` / `SHOPKEEPER`** — nomes mais expressivos em inglês do que `COMMON`/`MERCHANT`. Mapeados 1:1 para o conceito de "comum"/"lojista" do enunciado.

3. **Campos `cpf` e `cnpj` separados** no `register` (em vez de um `document` ambíguo), porque:
   - Cada um tem formato, máscara e dígito verificador próprios (VO `Cpf` vs `Cnpj`);
   - Lojista tem CNPJ, usuário comum tem CPF — explicito no payload.
   - O `login`, por sua vez, aceita `document` (único) porque busca por ambos.

4. **`payer` e `payee` no corpo da requisição** — o endpoint segue o contrato literal do enunciado: `POST /api/v1/transfer {value, payer, payee}`. Os IDs são UUID (PK do sistema) em vez de inteiros — os exemplos do enunciado usam inteiros mas não especificam o tipo de PK.

5. **Notificação via AMQP (RabbitMQ) em vez de `Coroutine::create()`** — o enunciado sugere notificação "após o commit". A coroutine resolve firing-and-forget mas perde a mensagem se a app reiniciar ou o serviço de notificação cair. AMQP garante:
   - Mensagem persistida em fila
   - `ACK`/`NACK` explícito (retry automático via requeue)
   - Cliente HTTP externo desacoplado do request HTTP original
     O consumer roda como processo do próprio Hyperf via `#[Consumer]` annotation.

6. **`StubAuthorizer` em dev** — o mock real devolve `false` aleatoriamente (~50%), quebrando testes determinísticos. Em `APP_ENV=dev` um stub que sempre autoriza é usado; em `testing`/`production` o `GuzzleAuthorizer` real é usado e testado.

7. **Idempotência via Redis** — adicional, não pedida no enunciado, mas essencial para evitar débitos duplicados em retries de rede do cliente.

8. **Lock de carteiras ordenado por UUID** — duas transferências A→B e B→A concorrentes dariam deadlock com locks em ordem diferente. Ordenar as chaves antes de `lockForUpdate` evita isso.

---

## Melhorias planejadas

Lista honesta de pontos que ainda não estão ideais e que serão endereçados:

- [x] Elevar PHPStan para nível 6 (sobre `app/`) e adicioná-lo ao job de lint do CI.
- [ ] Elevar PHPStan para nível 8 (exige tratamento de null nas leituras de model).
- [x] Purificar a camada Application: remover imports `Hyperf\Contract\StdoutLoggerInterface` e `Hyperf\Database\Exception\QueryException` (substituir por `Psr\Log\LoggerInterface` e por uma exceção de domínio própria).
- [ ] Implementar private constructor + named constructors nos VOs (`Cpf`, `Cnpj`, `Email`, `Money`), conforme padrão da codebase.
- [x] `GuzzleNotifier` não engole mais `GuzzleException`: a exceção propaga para o consumer dar NACK/requeue em vez de perder a notificação.
- [x] Corrigir typo `on_delete` → `onDelete` na migration de `transactions` (já usa `onDelete('restrict')` nas duas FKs).
- [ ] Adicionar testes de feature que exercitem o `GuzzleAuthorizer` (e `GuzzleNotifier`) reais, marcados `@group external`.
- [ ] Adicionar correlation ID / request ID propagado nos logs.
- [ ] README em inglês (mantendo o PT-BR atual) para audiência internacional.

---

## Licença

Apache-2.0 (ver `composer.json`).

## Autor

Gabriel Assunção — [github.com/gabigol](https://github.com/gabigol)