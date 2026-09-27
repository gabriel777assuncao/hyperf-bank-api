# Hyperf Bank API

API RESTful de pagamentos simplificada (PicPay-like) com 2 tipos de usuário, carteira digital e transferências, consulta a um **autorizador externo** e **notificação assíncrona** via mensageria.

PHP 8.3 + Hyperf 3.x (Swoole), arquitetura modular em camadas (DDD / Ports & Adapters).

---

## Stack

| Camada           | Tecnologia                                     |
|------------------|------------------------------------------------|
| Linguagem        | PHP 8.3                                        |
| Framework        | Hyperf 3.2 (Swoole)                            |
| BD relacional    | MySQL 8.0                                      |
| Cache / lock     | Redis 7                                        |
| Mensageria       | RabbitMQ 3 (AMQP)                              |
| Auth             | firebase/php-jwt (HS256)                       |
| Análise estática | PHPStan 2 nível 6, ECS (PSR-12)                |
| Testes           | PHPUnit + co-phpunit + Mockery                 |

---

## Arquitetura

Monolito modular com inversão de dependência nas bordas de I/O. Cada módulo tem três camadas:

- **Domain** — entidades, value objects, enums, exceções e contratos. Zero dependência de framework.
- **Application** — use cases que orquestram o fluxo de negócio. Dependem apenas de interfaces.
- **Infrastructure** — implementações concretas (repositórios Eloquent, clientes HTTP, JWT, AMQP, controllers).

### Módulos

| Módulo        | Responsabilidade                                                                |
|---------------|----------------------------------------------------------------------------------|
| **Common**    | Contratos cross-module, `AbstractController`, `AbstractResource`, ExceptionHandler |
| **User**      | Entidade `User`, VOs (`Cpf`, `Cnpj`, `Email`, `Password`), `UserType`           |
| **Auth**      | Registro, login, JWT (`AuthContract` ↔ `JwtAuthService`), `JwtAuthMiddleware`   |
| **Wallet**    | Entidade `Wallet`, `Money` VO, débito/crédito com `lockForUpdate`                |
| **Transaction** | `TransferUseCase`, `AuthorizerContract`, `NotifierContract`, publisher/consumer AMQP |

### Ports & Adapters

| Port                        | Dev                | Prod               |
|-----------------------------|--------------------|--------------------|
| `AuthorizerContract`        | `StubAuthorizer`   | `GuzzleAuthorizer` |
| `NotifierContract`          | `GuzzleNotifier`   | `GuzzleNotifier`   |
| `OutboxPublisherContract`   | `AmqpOutboxPublisher` | `AmqpOutboxPublisher` |

---

## Postman

A coleção com o happy path completo (health → register → login → transfer) está em `postman/hyperf-bank-api.postman_collection.json`. Importe o arquivo no Postman e execute as requests na ordem — cada uma popula as variáveis usadas pela seguinte (`token`, `payer_id`, `payee_id`).

> Execute com `APP_ENV=dev` (padrão) para que o `StubAuthorizer` sempre autorize as transferências.

---

## Subindo com Docker

```bash
cp .env.example .env
# gere um JWT_SECRET forte e cole no .env:
openssl rand -hex 32

docker compose up -d --build
docker compose exec app php bin/hyperf.php migrate --force

curl http://localhost:9501/health  # → OK
```

A API fica em `http://localhost:9501`. RabbitMQ Management em `http://localhost:15672`.

---

## Variáveis de ambiente

| Variável              | Descrição                                        | Default                         |
|-----------------------|--------------------------------------------------|---------------------------------|
| `APP_ENV`             | `dev` usa `StubAuthorizer`; qualquer outro, real | `dev`                           |
| `APP_PORT`            | Porta da app                                     | `9501`                          |
| `DB_*`                | Conexão MySQL                                    | `hyperf` / `secret`             |
| `REDIS_*`             | Conexão Redis                                    | `redis:6379` / `secret`         |
| `RABBITMQ_*`          | Conexão RabbitMQ                                 | `rabbitmq:5672` / `hyperf:secret` |
| `JWT_SECRET`          | **Obrigatório em produção** — chave HS256        | `local-dev-secret-...`          |
| `JWT_TTL`             | TTL do token em segundos                         | `3600`                          |

---

## Modelagem de dados

```
users (uuid PK)
 ├─ full_name, email (UNIQUE), password (bcrypt)
 ├─ cpf  varchar UNIQUE NULLABLE   # usuário comum
 ├─ cnpj varchar UNIQUE NULLABLE   # lojista
 └─ type  NORMAL | SHOPKEEPER

wallets (uuid PK)
 ├─ user_id  uuid UNIQUE FK→users   # 1:1, criada no registro
 └─ balance  BIGINT UNSIGNED        # centavos

transactions (uuid PK)
 ├─ payer_id, payee_id  FK→users
 ├─ value   BIGINT UNSIGNED
 └─ status  pending | completed | failed

outbox_events (uuid PK)
 ├─ aggregate_id   uuid FK→transactions   # 1 transação : N eventos
 ├─ status         pending | published | failed
 ├─ tries          UNSIGNED INT DEFAULT 0  # tentativas de publicação
 ├─ next_retry_at  datetime NULL           # backoff exponencial (NULL = imediato)
 ├─ payload        JSON (metadados da transação + notificação)
 └─ published_at   datetime NULL
```

---

## Endpoints

Base: `http://localhost:9501/api/v1`

| Método | Path        | Auth | Descrição                        |
|--------|-------------|------|----------------------------------|
| `GET`  | `/health`   | —    | Health check                     |
| `POST` | `/register` | —    | Cadastra usuário + cria carteira  |
| `POST` | `/login`    | —    | Autentica e devolve JWT           |
| `POST` | `/transfer` | JWT  | Realiza transferência             |

### Register

```json
POST /api/v1/register
{ "full_name": "João Silva", "cpf": "123.456.789-09", "cnpj": null,
  "email": "joao@example.com", "password": "senha123", "type": "NORMAL" }
```

Resposta `201` com `{ data: { token, user } }`. Erros: `422` (validação), `409` (conflito).

### Login

```json
POST /api/v1/login
{ "document": "123.456.789-09", "password": "senha123" }
```

`document` aceita CPF ou CNPJ em qualquer formato. Erros: `404`, `401`.

### Transfer

```json
POST /api/v1/transfer
Idempotency-Key: <uuid-opcional>
{ "value": 100.0, "payer": "<uuid>", "payee": "<uuid>" }
```

Erros: `404` (usuário), `409` (idempotency key em uso), `422` (regra de negócio), `503` (autorizador indisponível).

---

## Fluxo de transferência

Orquestrado por `TransferUseCase`:

1. `assertNotSelfTransfer` — payer ≠ payee
2. Carrega payer → `assertPayerCanTransfer` (SHOPKEEPER não envia)
3. Carrega payee
4. `AuthorizerContract::authorize()` — **antes** de abrir a transação
5. `DatabaseManager::transaction`:
   - `lockForUpdate` nas carteiras em ordem lexicográfica dos UUIDs (evita deadlock)
   - `payer.debit(money)` → `payee.credit(money)`
   - Persiste wallets e transaction com status `completed`
   - Grava `OutboxEvent` com status `pending` (outbox pattern, mesma transação)
6. Retorna `201`

O relay `OutboxRelayCrontab` (a cada 5s, `onOneServer` + `singleton`) busca eventos `pending` cujo aggregate está `completed` **e cujo `next_retry_at` está vencido** (`NULL` ou `<= NOW()`), publica na fila AMQP `transfer.completed` e marca como `published`. O `TransferNotificationConsumer` consome a fila e chama `NotifierContract::notify(payee)` — `ACK` no sucesso, `NACK` (requeue) na falha.

Em falha de publicação, o evento permanece `pending` e é reagendado com **backoff exponencial**: `next_retry_at = agora + min(2^tries, max_delay_seconds)`. Ao atingir `max_tries` tentativas, é marcado como `failed` (terminal).

| Tentativa | Delay |
|-----------|-------|
| 1ª falha  | 2s    |
| 2ª falha  | 4s    |
| 3ª falha  | 8s    |
| 4ª falha  | 16s   |
| 5ª falha  | `failed` (terminal) |

Configurável via env: `OUTBOX_MAX_TRIES` (default `5`), `OUTBOX_MAX_DELAY_SECONDS` (default `60`), `OUTBOX_BATCH_SIZE` (default `100`).

---

## Regras de negócio

| # | Regra |
|---|-------|
| 1 | `SHOPKEEPER` não envia, apenas recebe |
| 2 | Saldo insuficiente rejeitado em `Wallet::debit` |
| 3 | Autorizador consultado antes da transação de BD |
| 4 | Transferência atômica (débito + crédito + persistência) |
| 5 | Notificação publicada via outbox + relay (at-least-once) |
| 6 | `Money` sempre em centavos (`BIGINT UNSIGNED`) |
| 7 | Self-transfer bloqueado em FormRequest e UseCase |
| 8 | CPF, CNPJ e e-mail únicos no banco |
| 9 | Validação de dígitos CPF/CNPJ (algoritmo oficial) nos VOs |

### Exceções de domínio → HTTP

| Exceção                         | HTTP |
|---------------------------------|------|
| `UserNotFoundException`         | 404  |
| `InvalidCredentialsException`   | 401  |
| `UserAlreadyExistsException`    | 409  |
| `SelfTransferException`         | 422  |
| `UnauthorizedTransferException` | 422  |
| `NotAuthorizedException`        | 422  |
| `InsufficientBalanceException`  | 422  |
| `AuthorizerUnavailableException`| 503  |

---

## Idempotência

`IdempotencyMiddleware` no `/api/v1/transfer`:

- Sem header → comportamento normal.
- Primeira vez com header → processa e armazena a resposta 2xx no Redis por 24h.
- Repetição → devolve a mesma resposta com `X--idempotent-Replay: true`.
- Key em processamento concorrente → `409`.

---

## Testes

```bash
composer test:unit                                              # unit, rápidos, sem BD
docker compose exec app vendor/bin/co-phpunit -c phpunit.integration.xml  # integration
composer test                                                   # tudo (padrão no CI)
```

~130 métodos (unit + feature), 176 casos com data providers. Usa `FakeAuthorizer` para isolar o autorizador externo.

---

## CI

`.github/workflows/ci.yml` — dispara em PR e push em `main`/`develop`:

1. **build** — sobe `docker-compose.ci.yml`, aguarda `/health`.
2. **lint** — ECS + PHPStan nível 6.
3. **test** _(needs: build, lint)_ — migrations + unit + integration.

---

## Decisões de projeto

1. **Versionamento `/api/v1`** — facilita evolução sem quebrar clientes.
2. **`cpf` e `cnpj` separados no register** — cada um tem VO próprio com validação de dígitos; o login usa `document` unificado.
3. **IDs como UUID** — o enunciado usa inteiros, mas UUIDs evitam enumeração e são mais seguros em APIs públicas.
4. **Outbox pattern + relay via crontab** em vez de publicar direto no request — garante atomicidade entre persistência e evento, com publicação assíncrona confiável (at-least-once).
5. **Retry com backoff exponencial no relay** — `tries`/`next_retry_at` com `max_tries` configurável; falhas transitórias não descartam o evento e o intervalo cresce para não sobrecarregar um serviço externo instável.
6. **Lock de carteiras ordenado por UUID** — evita deadlock em transferências concorrentes A→B e B→A.
7. **Idempotência via Redis** — não pedida no enunciado, mas essencial para evitar débitos duplicados em retries de rede.

---
