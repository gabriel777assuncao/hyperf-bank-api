# Project Context

## Stack
- PHP 8.3 + Hyperf 3.x (Swoole)
- MySQL 8 — dados relacionais
- Redis — cache e idempotência
- RabbitMQ (AMQP) — mensageria para notificação assíncrona
- Docker + docker-compose
- PHPStan + ECS (Easy Coding Standard)
- firebase/php-jwt para autenticação

## Arquitetura
Modular Layered Architecture — monolito modular com inversão de dependência nas bordas de I/O.

### Módulos em app/
| Módulo        | Responsabilidade                                      |
|---------------|-------------------------------------------------------|
| Common        | Contratos cross-module (AuthContract), base classes (AbstractController, Model), ExceptionHandler |
| User          | User entity, VOs (Cpf, Cnpj, Email, Password), repositório |
| Auth          | Registro, login, autenticação JWT, middleware auth    |
| Transaction   | Fluxo de transferência, autorizador, notificador      |
| Wallet        | Saldo, débito, crédito, Money VO                      |

### Camadas dentro de cada módulo
- **Application** → orquestra (UseCases)
- **Domain** → regras de negócio puras (Entities, VOs, Enums, Exceptions)
- **Infrastructure** → implementações concretas (Repositories, Models Eloquent, clientes HTTP, JWT)
- **Http** → controllers, requests (FormRequest), exception handler

### Regra de dependência
- Application depende de interfaces (Contracts) definidas em Domain/Contract (e Common/Infrastructure/Contract)
- Application e Domain não importam nada de Hyperf
- Http depende apenas de Application
- Infrastructure implementa os contratos — nunca é importada por Application diretamente
- Common contém apenas abstrações e contratos cross-module

## Regras de negócio críticas
1. Lojista (UserType::SHOPKEEPER) NUNCA envia transferência; usuário comum é NORMAL
2. Saldo insuficiente — verificado em Wallet::debit (invariante de domínio)
3. Autorizador externo consultado ANTES de abrir Db::transaction
4. Db::transaction envolve: lock ordenado das carteiras + débito payer + crédito payee + persist Transaction
5. Notificação publicada em fila AMQP (RabbitMQ) APÓS o commit; consumer chama o notificador externo com ACK/NACK
6. Money sempre em centavos (BIGINT) — jamais float

## Serviços externos
- Autorizador: GET https://util.devi.tools/api/v2/authorize
- Notificador: POST https://util.devi.tools/api/v1/notify

## Endpoints
Base: `/api/v1`.

### Autenticação
POST /api/v1/register
Content-Type: application/json
{ "full_name": "João Silva", "cpf": "123.456.789-09", "cnpj": null, "email": "joao@example.com", "password": "password123", "type": "NORMAL" }

POST /api/v1/login
Content-Type: application/json
{ "document": "123.456.789-09", "password": "password123" }

### Transferência
POST /api/v1/transfer
Content-Type: application/json
{ "value": 100.0, "payer": "<uuid>", "payee": "<uuid>" }

> Contrato fiel ao enunciado. IDs são UUID (PK do sistema).

## Convenções de código
- Early return — sem else encadeado
- Exceções de domínio em Domain/Exception/, mapeadas no ExceptionHandler global
- Value Objects imutáveis, com validação no construtor
- Eloquent models em Infrastructure/Model/ do respectivo módulo — Application nunca importa model diretamente
- Injeção via construtor, nunca #[Inject] em Application ou Domain
- PSR-12 obrigatório (ECS configurado)
- Validação via construtores dos VOs — exceptions de domínio mapeadas no ExceptionHandler
- Resources para formatação de resposta — controller monta envelope `{ "data": ... }` via AbstractResource

## Estrutura de pastas
app/
├── Common/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
│       ├── Model.php
│       ├── Http/
│       │   ├── AbstractController.php
│       │   └── AbstractResource.php
│       ├── Exception/
│       │   ├── AppExceptionHandler.php
│       │   └── DomainExceptionHandler.php
│       └── Listener/
│           ├── DbQueryExecutedListener.php
│           └── ResumeExitCoordinatorListener.php
├── User/
│   ├── Domain/
│   │   ├── Entity/User.php
│   │   ├── ValueObject/Cpf.php
│   │   ├── ValueObject/Cnpj.php
│   │   ├── ValueObject/Email.php
│   │   ├── ValueObject/Password.php
│   │   ├── Enum/UserType.php
│   │   └── Exception/
│   ├── Application/
│   └── Infrastructure/
│       ├── Contract/UserRepositoryContract.php
│       ├── Repository/UserRepository.php
│       └── Model/UserModel.php
├── Auth/
│   ├── Domain/
│   │   └── AuthContract.php
│   ├── Application/
│   │   ├── RegisterUseCase.php
│   │   └── LoginUseCase.php
│   └── Infrastructure/
│       ├── JwtAuthService.php
│       └── Http/
│           ├── Controller/AuthController.php
│           ├── Request/
│           ├── Resource/AuthResponseResource.php
│           └── Middleware/JwtAuthMiddleware.php
├── Wallet/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
│       └── Model/WalletModel.php
└── Transaction/
    ├── Domain/
    ├── Application/
    └── Infrastructure/
        └── Model/TransactionModel.php
