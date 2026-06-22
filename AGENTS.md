# Project Context

## Stack
- PHP 8.3 + Hyperf 3.x (Swoole)
- MySQL 8 — dados relacionais
- Redis — cache e sessão
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
- Application depende de interfaces definidas em Infrastructure/Contract
- Application e Domain não importam nada de Hyperf
- Http depende apenas de Application
- Infrastructure implementa os contratos — nunca é importada por Application diretamente
- Common contém apenas abstrações e contratos cross-module

## Regras de negócio críticas
1. Lojista (type = merchant) NUNCA envia transferência
2. Saldo insuficiente — verificar antes de abrir transação
3. Autorizador externo consultado ANTES de abrir Db::transaction
4. Db::transaction envolve: débito payer + crédito payee + persist Transfer
5. Notificação disparada com Coroutine::create() APÓS o commit
6. Money sempre em centavos (BIGINT) — jamais float

## Serviços externos
- Autorizador: GET https://util.devi.tools/api/v2/authorize
- Notificador: POST https://util.devi.tools/api/v1/notify

## Endpoints
### Autenticação
POST /register
Content-Type: application/json
{ "full_name": "João Silva", "document": "12345678901", "email": "joao@example.com", "password": "password123", "type": "COMMON" }

POST /login
Content-Type: application/json
{ "document": "12345678901", "password": "password123" }

### Transferência (protegido — requer JWT)
POST /transfer
Authorization: Bearer <token>
Content-Type: application/json
{ "value": 100.0, "payer": 4, "payee": 15 }

## Convenções de código
- Early return — sem else encadeado
- Exceções de domínio em Domain/Exception/, mapeadas no ExceptionHandler global
- Value Objects imutáveis, construtor privado + named constructor
- Eloquent models em Infrastructure/Model/ do respectivo módulo — Application nunca importa model diretamente
- Injeção via construtor, nunca #[Inject] em Application ou Domain
- PSR-12 obrigatório (ECS configurado)
- Validação via construtores dos VOs — exceptions de domínio mapeadas no ExceptionHandler
- Resources para formatação de resposta — controller monta envelope `{ "data": ... }` via AbstractResource

## Estrutura de pastas
app/
├── Common/
│   ├── Domain/
│   │   └── AuthContract.php
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
