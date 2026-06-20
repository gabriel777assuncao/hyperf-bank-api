# Project Context

## Stack
- PHP 8.3 + Hyperf 3.x (Swoole)
- MySQL 8 — dados relacionais
- Redis — cache e sessão
- Docker + docker-compose
- PHPStan + ECS (Easy Coding Standard)

## Arquitetura
Modular Layered Architecture — monolito modular com inversão de dependência nas bordas de I/O.

### Módulos em app/
| Módulo        | Responsabilidade                                      |
|---------------|-------------------------------------------------------|
| Transaction   | Fluxo de transferência, autorizador, notificador      |
| Wallet        | Saldo, débito, crédito, Money VO                      |
| Shared        | User entity, VOs (Cpf, Email), models Eloquent, ExceptionHandler |

### Camadas dentro de cada módulo
- **Application** → orquestra
- **Domain** → regras de negócio puras, sem dependência de framework
- **Infrastructure** → implementações concretas (repositórios, clientes HTTP)
- **Http** → controllers, requests, exception handler

### Regra de dependência
- Application depende de interfaces definidas em Infrastructure/Contract
- Domain não importa nada de Hyperf
- Http depende apenas de Application
- Infrastructure implementa os contratos — nunca é importada por Application diretamente

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

## Endpoint principal
POST /transfer
Content-Type: application/json
{ "value": 100.0, "payer": 4, "payee": 15 }

## Convenções de código
- Early return — sem else encadeado
- Exceções de domínio em Domain/Exception/, mapeadas no ExceptionHandler global
- Value Objects imutáveis, construtor privado + named constructor
- Eloquent models em Shared/Model/ — Application nunca importa model diretamente
- Injeção via construtor, nunca #[Inject] em Application ou Domain
- PSR-12 obrigatório (ECS configurado)

## Estrutura de pastas
app/
├── Transaction/
│   ├── Application/Service/TransferService.php
│   ├── Application/DTO/TransferInput.php
│   ├── Domain/Entity/Transfer.php
│   ├── Domain/Exception/
│   ├── Infrastructure/Contract/
│   ├── Infrastructure/External/
│   ├── Infrastructure/Repository/
│   └── Http/
├── Wallet/
│   ├── Domain/Entity/Wallet.php
│   ├── Domain/ValueObject/Money.php
│   └── Infrastructure/
└── Shared/
    ├── Domain/Entity/User.php
    ├── Domain/ValueObject/
    ├── Model/
    └── Http/ExceptionHandler.php
