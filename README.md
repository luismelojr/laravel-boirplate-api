# Laravel SaaS Boilerplate

Um boilerplate de API SaaS completo, multi-tenant, domain-driven e API-first em Laravel 13 + PHP 8.5. Projetado para ser o ponto de partida para qualquer produto SaaS que precise de autenticação robusta, multi-tenancy, RBAC, uploads, backups e monitoramento prontos para produção.

---

## Sumário

- [Stack](#stack)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Comandos do dia a dia](#comandos-do-dia-a-dia)
- [Arquitetura](#arquitetura)
  - [Estrutura de pastas](#estrutura-de-pastas)
  - [Fluxo de uma requisição](#fluxo-de-uma-requisição)
- [Multi-tenancy](#multi-tenancy)
- [Modelos](#modelos)
- [Traits](#traits)
- [Enums](#enums)
- [DTOs (Spatie Laravel Data)](#dtos-spatie-laravel-data)
- [Services](#services)
- [Controllers](#controllers)
- [Form Requests](#form-requests)
- [Resources](#resources)
- [Rotas da API](#rotas-da-api)
- [Respostas da API (ApiResponse)](#respostas-da-api-apiresponse)
- [Tratamento de Exceções](#tratamento-de-exceções)
- [Rate Limiting](#rate-limiting)
- [RBAC — Roles & Permissions](#rbac--roles--permissions)
- [Notificações de E-mail](#notificações-de-e-mail)
- [Uploads de Mídia](#uploads-de-mídia)
- [Health Checks](#health-checks)
- [Backups](#backups)
- [Schedule (Agendamento)](#schedule-agendamento)
- [Filas e Horizon](#filas-e-horizon)
- [Telescope](#telescope)
- [Documentação automática (Scramble)](#documentação-automática-scramble)
- [Banco de Dados](#banco-de-dados)
- [Seeders](#seeders)
- [Factories & Estados de Teste](#factories--estados-de-teste)
- [Testes (Pest v4)](#testes-pest-v4)
- [Testes de Arquitetura (ArchTest)](#testes-de-arquitetura-archtest)
- [Infraestrutura (Docker / Sail)](#infraestrutura-docker--sail)
- [Variáveis de Ambiente](#variáveis-de-ambiente)
- [Serviços e dashboards locais](#serviços-e-dashboards-locais)
- [Como adicionar um novo domínio](#como-adicionar-um-novo-domínio)
- [Convenções de código](#convenções-de-código)

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.5 |
| Framework | Laravel 13 |
| Banco de dados | MySQL 8.4 |
| Cache / Queues | Redis |
| Autenticação | Laravel Sanctum v4 |
| Multi-tenancy | `spatie/laravel-multitenancy` v4 |
| RBAC | `spatie/laravel-permission` v8 |
| DTOs | `spatie/laravel-data` v4 |
| Media/Uploads | `spatie/laravel-medialibrary` v11 |
| Health checks | `spatie/laravel-health` v1 |
| Backup | `spatie/laravel-backup` v10 |
| Query Builder | `spatie/laravel-query-builder` v7 |
| Schedule Monitor | `spatie/laravel-schedule-monitor` v4 |
| Alertas Discord | `spatie/laravel-discord-alerts` v1 |
| Excel/CSV | `spatie/simple-excel` v3 |
| Auditoria | `owen-it/laravel-auditing` v14 |
| E-mail transacional | `resend/resend-laravel` v1 |
| API Docs | `dedoc/scramble` v0.13 |
| Filas | Laravel Horizon v5 |
| WebSockets | Laravel Reverb v1 |
| Feature flags | Laravel Pennant v1 |
| Monitoramento produção | Laravel Pulse v1 |
| State machines | `spatie/laravel-model-states` v2 |
| Slugs | `spatie/laravel-sluggable` v4 |
| PDF | `barryvdh/laravel-dompdf` v3 |
| Validações BR | `laravellegends/pt-br-validator` v13 |
| Error tracking | `sentry/sentry-laravel` v4 |
| Análise estática | `larastan/larastan` v3 (dev) |
| Debug local | Laravel Telescope v5 |
| Storage S3 | `league/flysystem-aws-s3-v3` v3 |
| Dev | Laravel Sail v1 (Docker) |
| Testes | Pest v4, PestPHP Arch, PHPUnit v12 |
| Formatter | Laravel Pint v1 |
| Locale | pt_BR |

---

## Requisitos

- Docker Desktop
- PHP 8.3+ (apenas para rodar `composer install` fora do Sail)
- Composer 2

---

## Instalação

```bash
# 1. Clone o repositório
git clone <repo-url> meu-projeto
cd meu-projeto

# 2. Copie o .env
cp .env.example .env

# 3. Instale as dependências
composer install

# 4. Suba os containers
vendor/bin/sail up -d

# 5. Gere a chave da aplicação
vendor/bin/sail artisan key:generate

# 6. Execute as migrations
vendor/bin/sail artisan migrate --no-interaction

# 7. Popule o banco com os seeders
vendor/bin/sail artisan db:seed --no-interaction

# 8. (Opcional) Compile os assets front-end
vendor/bin/sail npm install
vendor/bin/sail npm run build
```

Acesse: `http://localhost`

---

## Comandos do dia a dia

```bash
# Serviços
vendor/bin/sail up -d                                          # Subir containers
vendor/bin/sail stop                                           # Parar containers

# Testes
vendor/bin/sail artisan test --compact                         # Todos os testes
vendor/bin/sail artisan test --compact --filter=AuthTest       # Filtro por nome

# Migrations
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail artisan migrate:rollback

# Seeders
vendor/bin/sail artisan db:seed --no-interaction

# Novo teste Pest
vendor/bin/sail artisan make:test --pest NomeDaFeatureTest

# Formatar PHP (obrigatório após qualquer mudança PHP)
vendor/bin/sail bin pint --dirty --format agent

# Limpar caches
vendor/bin/sail artisan optimize:clear

# Análise estática (PHPStan via Larastan)
vendor/bin/sail composer analyse

# WebSocket server (Reverb)
vendor/bin/sail artisan reverb:start

# Feature flags
vendor/bin/sail artisan pennant:purge    # remove flags obsoletas

# Pulse (monitoramento)
# Acesse http://localhost/pulse após migrate

# Backup manual
vendor/bin/sail artisan backup:run --no-interaction

# Dev server completo (API + Queue + Logs + Vite)
vendor/bin/sail composer run dev

# Tinker (REPL PHP)
vendor/bin/sail artisan tinker --execute 'User::count();'

# Listar rotas
vendor/bin/sail artisan route:list --path=api

# Inspecionar configuração
vendor/bin/sail artisan config:show app
```

---

## Arquitetura

### Estrutura de pastas

```
app/
├── Domain/                         ← Lógica de negócio por domínio
│   ├── Auth/
│   │   ├── Data/                   ← DTOs (Spatie Laravel Data)
│   │   │   ├── AcceptInviteData.php
│   │   │   ├── ForgotPasswordData.php
│   │   │   ├── InviteUserData.php
│   │   │   ├── LoginUserData.php
│   │   │   ├── RegisterData.php
│   │   │   └── ResetPasswordData.php
│   │   └── Services/               ← Single-action services
│   │       ├── AcceptInviteService.php
│   │       ├── ForgotPasswordService.php
│   │       ├── InviteUserService.php
│   │       ├── LoginUserService.php
│   │       ├── LogoutUserService.php
│   │       ├── RegisterTenantService.php
│   │       ├── ResetPasswordService.php
│   │       └── VerifyEmailService.php
│   └── Tenant/
│       └── Finders/
│           └── HeaderTenantFinder.php  ← Resolve tenant pelo header X-Tenant-ID
│
├── Enums/
│   ├── TenantStatusEnum.php        ← active | inactive
│   └── UserStatusEnum.php          ← active | inactive | pending
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ApiController.php   ← Base: usa trait ApiResponse
│   │       └── V1/Dashboard/
│   │           ├── AuthController.php
│   │           └── Admin/
│   │               └── AdminUserController.php
│   ├── Middleware/
│   │   └── EnsureTenant.php        ← Resolve tenant ou retorna 422
│   ├── Requests/Api/V1/Dashboard/Auth/
│   │   ├── AcceptInviteRequest.php
│   │   ├── ForgotPasswordRequest.php
│   │   ├── InviteUserRequest.php
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   └── ResetPasswordRequest.php
│   └── Resources/Api/V1/Dashboard/
│       ├── Tenant/TenantResource.php
│       └── User/UserResource.php
│
├── Models/
│   ├── Tenant.php
│   ├── User.php
│   └── UserInvitation.php
│
├── Notifications/Auth/
│   ├── ForgotPasswordNotification.php
│   ├── InviteUserNotification.php
│   └── VerifyEmailNotification.php
│
├── Providers/
│   ├── AppServiceProvider.php      ← Rate limiting, health checks, email verify URL
│   ├── HorizonServiceProvider.php
│   └── TelescopeServiceProvider.php
│
├── Support/Api/
│   ├── ApiExceptionRegister.php    ← Handler centralizado de exceções para API
│   └── ApiResponseFactory.php      ← Factory estática de respostas JSON
│
└── Traits/
    ├── ApiResponse.php             ← Métodos de resposta para controllers
    ├── BelongsToTenant.php         ← Global scope + auto-set tenant_id
    └── HasUuid.php                 ← Auto-gera UUID no create

bootstrap/
└── app.php                         ← Middleware aliases, schedule, exception handler

config/
├── audit.php                       ← Auditoria de modelos
├── backup.php                      ← Config de backup S3/MinIO
├── health.php                      ← Health checks (DB, Redis, Horizon, Queue, Backup)
├── horizon.php                     ← Filas e workers
├── multitenancy.php                ← Tenant finder, migrations landlord
├── permission.php                  ← Guard sanctum
├── schedule-monitor.php            ← Monitor de agendamentos
└── scramble.php                    ← OpenAPI/Swagger

database/
├── factories/
│   ├── TenantFactory.php
│   ├── UserFactory.php
│   └── UserInvitationFactory.php
├── migrations/
│   ├── landlord/                   ← Migration da tabela tenants (carregada via AppServiceProvider)
│   └── *.php                       ← Demais migrations
└── seeders/
    ├── DatabaseSeeder.php
    ├── RoleSeeder.php
    ├── TenantSeeder.php
    └── UserSeeder.php

routes/
├── api.php                         ← Todas as rotas da API
├── console.php                     ← Comandos Artisan customizados
└── web.php                         ← Health + docs

tests/
├── Feature/                        ← Testes de feature (Pest)
├── ArchTest.php                    ← Testes de arquitetura
├── Pest.php                        ← Setup global + helper tenantActingAs()
└── TestCase.php
```

### Fluxo de uma requisição

```
HTTP Request
    ↓
[Middleware: ensure_tenant]            ← Lê X-Tenant-ID, chama Tenant::makeCurrent()
    ↓
[Middleware: auth:sanctum]             ← Valida Bearer token (quando a rota exige)
    ↓
[Middleware: role:admin]               ← Verifica role (quando a rota exige)
    ↓
[FormRequest]                          ← Valida os dados de entrada (mensagens em pt_BR)
    ↓
[Controller]                           ← Recebe FormRequest, instancia DTO
    ↓
[DTO::from($request->validated())]     ← Cria objeto tipado imutável
    ↓
[Service::handle($dto)]                ← Toda lógica de negócio aqui
    ↓
[Resource]                             ← Transforma o model em array de resposta
    ↓
[$this->success() / $this->created()]  ← ApiResponse trait formata o JSON final
    ↓
JSON Response: { success, message, data, meta? }
```

---

## Multi-tenancy

### Como funciona

- **Single database**: todos os dados de todos os tenants ficam no mesmo banco. Tabelas tenant-scoped têm coluna `tenant_id` com FK para `tenants.id`.
- **Identificação**: toda requisição (exceto `POST /api/v1/register`) deve enviar o header `X-Tenant-ID` com o UUID do tenant.
- **Middleware `ensure_tenant`** (`EnsureTenant.php`): chama `HeaderTenantFinder::findForRequest()`, que busca o tenant por UUID e status ativo. Se não encontrar, retorna `422`.
- **`Tenant::makeCurrent()`**: após encontrar o tenant, ele é definido como current. A partir daí, `Tenant::current()` está disponível globalmente na requisição.
- **`BelongsToTenant` trait**: adiciona global scope `WHERE tenant_id = current_tenant_id` em todos os models que usam a trait, e auto-preenche `tenant_id` no create.
- **Migrations landlord**: a tabela `tenants` fica em `database/migrations/landlord/` e é carregada separadamente via `AppServiceProvider::boot()` com `$this->loadMigrationsFrom(database_path('migrations/landlord'))`.

### HeaderTenantFinder

```php
// app/Domain/Tenant/Finders/HeaderTenantFinder.php
class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $uuid = $request->header('X-Tenant-ID');

        if (! $uuid) {
            return null;
        }

        return Tenant::query()
            ->where('uuid', $uuid)
            ->where('status', TenantStatusEnum::Active)
            ->first();
    }
}
```

Registrado em `config/multitenancy.php`:

```php
'tenant_finder' => \App\Domain\Tenant\Finders\HeaderTenantFinder::class,
```

### Isolamento em testes

```php
beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});
```

---

## Modelos

### `Tenant` (`app/Models/Tenant.php`)

Estende `Spatie\Multitenancy\Models\Tenant`.

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | int | Hidden na API |
| `uuid` | string | Público, usado como route key |
| `name` | string | Nome do tenant |
| `slug` | string | Único globalmente |
| `status` | `TenantStatusEnum` | `active` \| `inactive` |
| `deleted_at` | datetime | Soft delete, hidden na API |

**Traits:** `HasUuid`, `SoftDeletes`, `HasFactory`

**Route key:** `uuid`

**Casts:** `status → TenantStatusEnum`, `deleted_at → datetime`

---

### `User` (`app/Models/User.php`)

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | int | Hidden na API |
| `uuid` | string | Público, usado como route key |
| `tenant_id` | int | FK, hidden na API |
| `name` | string | |
| `email` | string | Único por tenant `(email, tenant_id)` |
| `email_verified_at` | datetime | null = não verificado |
| `password` | string | Hashed, nullable para pending users |
| `status` | `UserStatusEnum` | `active` \| `inactive` \| `pending` |
| `remember_token` | string | Hidden |
| `deleted_at` | datetime | Soft delete, hidden |

**Traits:** `BelongsToTenant`, `HasApiTokens`, `HasFactory`, `HasRoles`, `HasUuid`, `InteractsWithMedia`, `MustVerifyEmail`, `Notifiable`, `Auditable`, `SoftDeletes`

**Implements:** `Auditable`, `HasMedia`, `MustVerifyEmailContract`

**Route key:** `uuid`

**Media collection:** `avatar` → `singleFile()`, conversão `thumb` (150×150, `nonQueued`)

**Método sobrescrito:** `sendEmailVerificationNotification()` usa `VerifyEmailNotification` customizada (em pt_BR)

**Casts:** `email_verified_at → datetime`, `password → hashed`, `status → UserStatusEnum`

---

### `UserInvitation` (`app/Models/UserInvitation.php`)

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | int | |
| `tenant_id` | int | FK |
| `user_id` | int | FK para User |
| `token` | string | UUID único, enviado por e-mail |
| `expires_at` | datetime | 24h após criação |
| `accepted_at` | datetime | null = pendente |

**Relations:** `user()` → `BelongsTo(User)`, `tenant()` → `BelongsTo(Tenant)`

---

## Traits

### `HasUuid` (`app/Traits/HasUuid.php`)

Auto-gera um UUID v4 na criação do model, na coluna `uuid`. O route binding usa `uuid` como chave pública, evitando expor IDs inteiros sequenciais.

```php
// Boot automático via convenção bootHasUuid()
static::creating(function ($model): void {
    if (empty($model->uuid)) {
        $model->uuid = (string) Str::uuid();
    }
});

// Sobrescreva se a coluna tiver outro nome
public function getUuidColumn(): string
{
    return 'uuid';
}
```

---

### `BelongsToTenant` (`app/Traits/BelongsToTenant.php`)

Dois comportamentos automáticos via boot:

1. **Global scope**: adiciona `WHERE tenant_id = Tenant::current()->id` em todas as queries do model. Garante isolamento de dados entre tenants sem esforço manual.
2. **Auto-set no create**: ao criar um registro, preenche `tenant_id` automaticamente com o tenant atual.

```php
// Uso: apenas adicionar a trait ao model
class Post extends Model
{
    use BelongsToTenant;
}

// Relação disponível automaticamente:
$post->tenant; // BelongsTo(Tenant)
```

> **Atenção**: se `Tenant::current()` for null ao criar um registro (ex: em seeders ou comandos), um warning é logado e `tenant_id` ficará nulo. Sempre chame `$tenant->makeCurrent()` antes de criar registros tenant-scoped.

---

### `ApiResponse` (`app/Traits/ApiResponse.php`)

Usado em todos os controllers via herança de `ApiController`. **Nunca use `response()->json()` diretamente.**

```php
// Sucesso genérico (200)
$this->success(data: $resource, message: 'Mensagem');

// Criação (201)
$this->created(data: $resource, message: 'Recurso criado com sucesso');

// Sem conteúdo (204)
$this->noContent();

// Listagem paginada (200 + meta de paginação)
$this->paginated($paginator, 'Listagem');

// Erros
$this->error('Mensagem de erro', 400);
$this->notFound('Recurso não encontrado');            // 404
$this->unauthorized('Não autorizado');                // 401
$this->forbidden('Sem permissão');                    // 403
$this->validationError($errors, 'Erro de validação'); // 422
```

---

## Enums

### `TenantStatusEnum` (`app/Enums/TenantStatusEnum.php`)

```php
enum TenantStatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public static function values(): array; // ['active', 'inactive']
}
```

### `UserStatusEnum` (`app/Enums/UserStatusEnum.php`)

```php
enum UserStatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending'; // Convidado, ainda não definiu senha

    public static function values(): array; // ['active', 'inactive', 'pending']
}
```

**Regra de negócio:**
- `active` — usuário ativo, pode logar (desde que e-mail verificado)
- `inactive` — bloqueado, não consegue logar
- `pending` — convidado por admin, sem senha definida ainda; login bloqueado até aceitar o convite

---

## DTOs (Spatie Laravel Data)

Todos os DTOs ficam em `app/Domain/{Domínio}/Data/`. Estendem `Spatie\LaravelData\Data`. Campos sensíveis usam `#[Hidden]` para não aparecerem em logs ou serialização.

### `RegisterData`
```php
class RegisterData extends Data
{
    public function __construct(
        public readonly string $tenant_name,
        public readonly string $tenant_slug,
        public readonly string $name,
        public readonly string $email,
        #[Hidden] public readonly string $password,
    ) {}
}
```

### `LoginUserData`
```php
class LoginUserData extends Data
{
    public function __construct(
        public readonly string $email,
        #[Hidden] public readonly string $password,
    ) {}
}
```

### `ForgotPasswordData`
```php
class ForgotPasswordData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
```

### `ResetPasswordData`
```php
class ResetPasswordData extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
        #[Hidden] public readonly string $password,
    ) {}
}
```

### `InviteUserData`
```php
class InviteUserData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,
    ) {}
}
```

### `AcceptInviteData`
```php
class AcceptInviteData extends Data
{
    public function __construct(
        public readonly string $token,
        #[Hidden] public readonly string $password,
    ) {}
}
```

**Instanciação sempre via:**
```php
$dto = SomeData::from($request->validated());
```

---

## Services

Todos os services são **single-action classes** com método `handle()`. Ficam em `app/Domain/{Domínio}/Services/`.

**Regras:**
- Writes em banco: sempre `DB::transaction(fn() => ..., 3)` (retry 3x em deadlock)
- Após write: `$model->fresh()` antes de retornar
- Notificações/e-mails: sempre **fora** do closure da transaction
- Nunca `try/catch` que só faz `report()` + rethrow — Laravel já trata automaticamente

### `RegisterTenantService`

Cria tenant + usuário admin atomicamente em uma transaction. Atribui role `admin` ao usuário. Envia verificação de e-mail fora da transaction. Retorna `['tenant', 'user', 'token']`.

### `LoginUserService`

Valida credenciais, bloqueia usuários inativos e não verificados. Revoga tokens anteriores com nome `auth_token` antes de criar novo. Retorna `['user', 'token']`.

### `LogoutUserService`

Revoga o token atual do usuário autenticado.

### `ForgotPasswordService`

Busca usuário pelo e-mail sem enumeração (retorna silenciosamente se não encontrar). Gera token aleatório (60 chars), armazena como SHA-256 na tabela `password_reset_tokens` com `tenant_id`. Envia `ForgotPasswordNotification`.

### `ResetPasswordService`

Valida token SHA-256 + expiração de 1h + `tenant_id`. Atualiza senha. Auto-verifica e-mail se ainda não verificado. Deleta o token usado.

### `InviteUserService`

Cria usuário com status `pending` e senha aleatória temporária (inusável). Cria `UserInvitation` com token UUID e validade de 24h. Envia `InviteUserNotification` fora da transaction. Retorna `User`.

### `AcceptInviteService`

Valida token, já-utilizado e expiração. Em transaction: define senha real, verifica e-mail, ativa conta (`status = active`), marca convite como aceito. Retorna `['user', 'token']`.

### `VerifyEmailService`

- `verify($user, $hash)`: valida hash do e-mail com `hash_equals` e marca como verificado.
- `resend($user)`: reenvia a notificação de verificação.

---

## Controllers

### `ApiController` (base)

```php
// app/Http/Controllers/Api/ApiController.php
abstract class ApiController extends Controller
{
    use ApiResponse;
}
```

Todos os controllers de API estendem `ApiController`.

### `AuthController`

`app/Http/Controllers/Api/V1/Dashboard/AuthController.php`

| Método | Action | Service usado |
|--------|--------|--------------|
| `register` | POST /register | `RegisterTenantService` |
| `login` | POST /login | `LoginUserService` |
| `logout` | POST /logout | `LogoutUserService` |
| `me` | GET /me | — |
| `forgotPassword` | POST /forgot-password | `ForgotPasswordService` |
| `resetPassword` | POST /reset-password | `ResetPasswordService` |
| `resendVerification` | POST /email/resend | `VerifyEmailService` |
| `acceptInvite` | POST /invite/accept | `AcceptInviteService` |
| `verifyEmail` | GET /email/verify/{id}/{hash} | `VerifyEmailService` |

### `AdminUserController`

`app/Http/Controllers/Api/V1/Dashboard/Admin/AdminUserController.php`

| Método | Action | Service usado |
|--------|--------|--------------|
| `invite` | POST /admin/users/invite | `InviteUserService` |

---

## Form Requests

Localização: `app/Http/Requests/Api/V1/Dashboard/Auth/`

**Regras:**
- `authorize()` sempre retorna `true` (autorização é feita nos middlewares)
- Mensagens de erro sempre em português
- Validação de unicidade de e-mail por tenant:

```php
Rule::unique('users')->where(fn($q) => $q->where('tenant_id', Tenant::current()->getKey()))
```

---

## Resources

### `UserResource`

```json
{
    "uuid": "9d1b2c3d-...",
    "name": "João Silva",
    "email": "joao@exemplo.com",
    "status": "active",
    "avatar_url": "http://localhost/storage/1/avatar.jpg",
    "created_at": "25/06/2026 10:00:00",
    "updated_at": "25/06/2026 10:00:00"
}
```

### `TenantResource`

```json
{
    "uuid": "9d1b2c3d-...",
    "name": "Minha Empresa",
    "slug": "minha-empresa",
    "status": "active",
    "created_at": "25/06/2026 10:00:00"
}
```

> IDs internos (`id`, `tenant_id`, `deleted_at`) são sempre `hidden` nos models e nunca aparecem nas respostas.

---

## Rotas da API

Base: `/api/v1`

| Método | Endpoint | Middleware | Descrição |
|--------|----------|-----------|-----------|
| POST | `/register` | `throttle:10,1` | Cria tenant + usuário admin |
| POST | `/login` | `ensure_tenant`, `throttle:auth` | Login (bloqueia não verificados e inativos) |
| POST | `/forgot-password` | `ensure_tenant`, `throttle:auth` | Solicita reset de senha |
| POST | `/reset-password` | `ensure_tenant`, `throttle:auth` | Redefine senha com token |
| POST | `/invite/accept` | `ensure_tenant` | Aceita convite e ativa conta |
| GET | `/email/verify/{id}/{hash}` | `ensure_tenant` | Verifica e-mail (signed URL, 60min) |
| POST | `/logout` | `ensure_tenant`, `auth:sanctum` | Revoga token atual |
| GET | `/me` | `ensure_tenant`, `auth:sanctum` | Dados do usuário autenticado |
| POST | `/email/resend` | `ensure_tenant`, `auth:sanctum` | Reenviar e-mail de verificação |
| POST | `/admin/users/invite` | `ensure_tenant`, `auth:sanctum`, `role:admin` | Convidar usuário (admin only) |

**Notas:**
- `POST /register` é a **única** rota sem `X-Tenant-ID`
- O nome `verification.verify` na rota de verificação é obrigatório para o signed URL funcionar
- Todas as rotas tenant-scoped retornam `422` se o header `X-Tenant-ID` estiver ausente ou o tenant estiver inativo

---

## Respostas da API (ApiResponse)

### Sucesso

```json
{
    "success": true,
    "message": "Mensagem",
    "data": { "uuid": "...", "name": "..." }
}
```

### Sucesso com paginação

```json
{
    "success": true,
    "message": "Listagem",
    "data": [ "..." ],
    "meta": {
        "pagination": {
            "total": 100,
            "per_page": 15,
            "current_page": 1,
            "last_page": 7,
            "from": 1,
            "to": 15
        }
    }
}
```

### Erro de validação

```json
{
    "success": false,
    "message": "Erro de validação",
    "errors": {
        "email": ["O campo email é obrigatório."]
    }
}
```

### `ApiResponseFactory` (uso fora de controllers)

Para middleware, exception handler ou qualquer contexto sem acesso ao trait:

```php
ApiResponseFactory::success($data, 'Mensagem', 200, $meta);
ApiResponseFactory::error('Mensagem', 422, $errors, $meta);
```

---

## Tratamento de Exceções

`app/Support/Api/ApiExceptionRegister.php` — registrado em `bootstrap/app.php`.

Intercepta exceções apenas para requests JSON ou prefixo `/api/*` e retorna respostas padronizadas:

| Exceção | HTTP | Mensagem padrão |
|---------|------|----------------|
| `ValidationException` | 422 | "Erro de validação" + errors |
| `AuthenticationException` | 401 | Mensagem da exception ou "Não autorizado" |
| `AuthorizationException` | 403 | "Sem permissão" |
| `AccessDeniedHttpException` | 403 | "Sem permissão" |
| `ModelNotFoundException` | 404 | "Recurso não encontrado" |
| `NotFoundHttpException` | 404 | "Recurso não encontrado" |
| `MethodNotAllowedHttpException` | 405 | "Método não permitido" |
| `TooManyRequestsHttpException` | 429 | "Muitas requisições" |
| `SpatieUnauthorizedException` | 403 | "Sem permissão" |
| `QueryException` (UUID inválido) | 422 | "UUID inválido" |
| `QueryException` (outros) | 500 | "Erro no banco de dados" |
| `Throwable` (fallback) | 500 | Mensagem da exception (debug) ou "Erro interno do servidor" |

Em modo debug (`APP_DEBUG=true`), erros 500 incluem `meta.exception` com o nome da classe da exception.

---

## Rate Limiting

Configurado em `AppServiceProvider::boot()`:

| Limiter | Limite | Chave | Aplicado em |
|---------|--------|-------|-------------|
| `auth` | 5 req/min | por IP | login, forgot-password, reset-password |
| `api` (autenticado) | 60 req/min | por `user.id` | todas as rotas tenant-scoped |
| `api` (anônimo) | 30 req/min | por IP | todas as rotas tenant-scoped |
| `register` | 10 req/min | por IP | POST /register |

Quando excedido, retorna `429` com mensagem "Muitas requisições".

---

## RBAC — Roles & Permissions

Usando `spatie/laravel-permission` com guard `sanctum`.

**Roles disponíveis** (criados pelo `RoleSeeder`):
- `admin` — acesso total, incluindo rotas `/admin/*`
- `user` — usuário padrão

**Guard configurado em `config/permission.php`:**
```php
'defaults' => ['guard' => 'sanctum'],
```

**Middleware aliases** (registrados em `bootstrap/app.php`):
```php
'role'               => RoleMiddleware::class,
'permission'         => PermissionMiddleware::class,
'role_or_permission' => RoleOrPermissionMiddleware::class,
```

**Uso em rotas:**
```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(fn() => ...);
Route::middleware(['auth:sanctum', 'permission:edit-users'])->group(fn() => ...);
```

**Uso em código:**
```php
$user->assignRole('admin');
$user->hasRole('admin');               // bool
$user->hasPermissionTo('edit-users');  // bool
$user->roles;                          // Collection de roles
```

**Gates para Horizon e Telescope** são definidos nos respectivos ServiceProviders e verificam `$user->hasRole('admin')`.

---

## Notificações de E-mail

Todas em `app/Notifications/Auth/`. Canal: `mail`.

### `VerifyEmailNotification`

Estende `Illuminate\Auth\Notifications\VerifyEmail`. Sobrescreve `buildMailMessage()` com texto em pt_BR. Assunto: "Verifique seu endereço de e-mail". Link expira em 60 minutos. URL gerada via signed route `verification.verify` com `uuid` (não `id`).

### `ForgotPasswordNotification`

Usa `Queueable`. Recebe o token em texto plano (armazenado como SHA-256 no banco). URL de reset: `{APP_URL}/reset-password?token={token}&email={email}`. Link expira em 1 hora.

### `InviteUserNotification`

Usa `Queueable`. Recebe token UUID e nome do tenant. URL do convite: `{APP_URL}/invite/accept?token={token}`. Convite expira em 24 horas.

> Em desenvolvimento, todos os e-mails são capturados pelo **Mailpit** em `http://localhost:8025`.

---

## Uploads de Mídia

Usando `spatie/laravel-medialibrary`.

**Para habilitar em um model:**

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)->height(200)->nonQueued();
    }
}
```

**Upload:**
```php
$post->addMediaFromRequest('cover')->toMediaCollection('cover');
```

**Obter URL:**
```php
$post->getFirstMediaUrl('cover');           // URL original
$post->getFirstMediaUrl('cover', 'thumb'); // URL da conversão thumb
```

**Disco padrão:** `public` (local). Produção: `MEDIA_DISK=s3`.

O `User` já vem configurado com a coleção `avatar` (150×150 thumb, `singleFile`, `nonQueued`).

---

## Health Checks

Endpoint público: `GET /health`

Checks configurados em `AppServiceProvider::boot()`:

| Check | O que verifica |
|-------|---------------|
| `DatabaseCheck` | Conexão com MySQL |
| `RedisCheck` | Conexão com Redis |
| `HorizonCheck` | Status do processo Horizon |
| `QueueCheck` | Se jobs estão sendo processados |
| `BackupsCheck` | Backup mais recente no S3 (max 2 dias de atraso) |
| `ScheduleCheck` | Se o scheduler está rodando (heartbeat a cada minuto) |

**Resultado armazenado** na tabela `health_check_result_history_items` por 5 dias.

---

## Backups

Usando `spatie/laravel-backup`.

**O que é feito backup:** dump do MySQL (sem arquivos estáticos).

**Destino:** S3 / MinIO (bucket configurado em `AWS_BUCKET`).

**Schedule:**
```
backup:run    → às 06:00 e 23:00 (twiceDaily)
backup:clean  → às 00:30 (dailyAt)
```

**Política de retenção:**

| Período | Quantidade |
|---------|-----------|
| Diário | 7 backups |
| Semanal | 4 backups |
| Mensal | 3 backups |
| Anual | 1 backup |

**Notificação em falha:** e-mail para `BACKUP_NOTIFICATION_EMAIL`.

**Config:** `config/backup.php`. Sem encriptação de zip e sem backup de arquivos.

---

## Schedule (Agendamento)

Definido em `bootstrap/app.php`:

| Comando | Frequência | Propósito |
|---------|-----------|-----------|
| `schedule-monitor:sync` | A cada hora | Sincroniza tasks monitoradas |
| `health:schedule-check-heartbeat` | A cada minuto | Mantém o `ScheduleCheck` do health vivo |
| `horizon:snapshot` | A cada 5 min | Coleta métricas de performance do Horizon |
| `backup:run` | 06:00 e 23:00 | Executa backup do banco |
| `backup:clean` | 00:30 | Remove backups antigos conforme retenção |

---

## Filas e Horizon

- **Driver:** Redis
- **Acesso ao dashboard:** `http://localhost/horizon` (requer role `admin`)
- **Gate** definido em `HorizonServiceProvider`

**Iniciar worker manualmente:**
```bash
vendor/bin/sail artisan horizon
```

Notificações com `Queueable` (`ForgotPasswordNotification`, `InviteUserNotification`) são processadas via queue automaticamente.

---

## Telescope

Ferramenta de debug disponível **apenas em ambiente local** (`APP_ENV=local`).

- **Acesso:** `http://localhost/telescope`
- **Gate:** requer role `admin`
- Registrado condicionalmente em `AppServiceProvider::register()` — não carrega em staging/produção

---

## Documentação automática (Scramble)

Gera documentação OpenAPI automaticamente a partir dos controllers e Form Requests.

- **Acesso local:** `http://localhost/docs/api`
- **Config:** `config/scramble.php`
- Anotações `@tags NomeDoGrupo` nos controllers organizam os endpoints por grupo na UI

---

## Banco de Dados

### Tabelas

| Tabela | Propósito | Tenant-scoped? |
|--------|-----------|:--------------:|
| `tenants` | Registry de tenants (landlord migration) | Não |
| `users` | Usuários, e-mail único por tenant | Sim |
| `user_invitations` | Convites pendentes com token | Sim |
| `password_reset_tokens` | Tokens de reset, PK `(email, tenant_id)` | Sim |
| `personal_access_tokens` | Tokens Sanctum | Via user |
| `roles` | Roles (admin, user) | Não |
| `permissions` | Permissions | Não |
| `model_has_roles` | Pivot user ↔ role | Via user |
| `model_has_permissions` | Pivot user ↔ permission | Via user |
| `role_has_permissions` | Pivot role ↔ permission | Não |
| `media` | Arquivos de mídia | Via model |
| `audits` | Histórico de mudanças nos models | Via model |
| `health_check_result_history_items` | Histórico de health checks | Não |
| `monitored_scheduled_tasks` | Tasks monitoradas | Não |
| `telescope_entries` | Dados de debug | Não |
| `sessions` | Sessões | Não |
| `cache` | Cache | Não |
| `jobs` | Fila de jobs | Não |
| `failed_jobs` | Jobs com falha | Não |

### Particularidades do schema

- `password_reset_tokens`: PK composta `(email, tenant_id)` — tokens são isolados por tenant
- `users`: unique constraint `(email, tenant_id)` — mesmo e-mail pode existir em tenants diferentes
- `users.password`: nullable — usuários `pending` não têm senha ainda
- Route binding sempre via `uuid`, nunca `id`
- IDs internos (`id`, `tenant_id`, `deleted_at`) sempre `hidden` nos models

---

## Seeders

Ordem obrigatória: `RoleSeeder → TenantSeeder → UserSeeder`

### `RoleSeeder`
Cria as roles `admin` e `user` com `guard_name = 'sanctum'` usando `firstOrCreate`.

### `TenantSeeder`
Cria (ou busca) o tenant admin baseado em `ADMIN_TENANT_SLUG` / `ADMIN_TENANT_NAME`. Chama `$tenant->makeCurrent()` para que o `UserSeeder` funcione corretamente.

### `UserSeeder`
Cria (ou busca) o usuário admin via `ADMIN_EMAIL` / `ADMIN_PASSWORD` e atribui role `admin`.

**Variáveis de ambiente para seeders:**
```env
ADMIN_EMAIL=admin@boirplate.test
ADMIN_PASSWORD=password
ADMIN_TENANT_NAME="Default Tenant"
ADMIN_TENANT_SLUG=default
```

---

## Factories & Estados de Teste

### `TenantFactory`

```php
Tenant::factory()->create();             // ativo
Tenant::factory()->inactive()->create(); // status: inactive
```

### `UserFactory`

```php
User::factory()->create();               // ativo + e-mail verificado (padrão)
User::factory()->inactive()->create();   // status: inactive
User::factory()->unverified()->create(); // email_verified_at: null
User::factory()->pending()->create();    // status: pending, password: null, não verificado
```

**Senha padrão das factories:** `password`

### `UserInvitationFactory`

```php
UserInvitation::factory()->create();            // válido, não aceito (24h)
UserInvitation::factory()->expired()->create();  // expires_at no passado
UserInvitation::factory()->accepted()->create(); // accepted_at preenchido
```

---

## Testes (Pest v4)

### Setup obrigatório para testes com multi-tenancy

```php
beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});
```

### Setup obrigatório para testes com roles

```php
beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
});
```

### Helper global: `tenantActingAs()`

Definido em `tests/Pest.php`. Autentica o usuário via Sanctum **e** define o tenant dele como current:

```php
$user = User::factory()->create();
tenantActingAs($user);

// Equivalente manual a:
$user->tenant->makeCurrent();
Sanctum::actingAs($user);
```

> Lança `RuntimeException` se o usuário não tiver tenant associado.

### Header obrigatório em requests tenant-scoped

```php
$this->withHeader('X-Tenant-ID', $this->tenant->uuid)
     ->postJson('/api/v1/login', ['email' => '...', 'password' => '...']);
```

### Testes de e-mail/notificação

```php
Notification::fake();

// ... ação que dispara notificação ...

Notification::assertSentTo($user, VerifyEmailNotification::class);
```

### Suíte atual (66 testes)

| Arquivo | Testes | Cobertura |
|---------|--------|-----------|
| `AuthTest.php` | 9 | Login, logout, /me, header ausente |
| `RoleTest.php` | 4 | Atribuição de role, middleware role:admin |
| `AvatarTest.php` | 3 | Upload de mídia, singleFile |
| `HealthTest.php` | 4 | Endpoint /health, DB check, Redis check |
| `BackupTest.php` | 3 | backup:clean, health inclui Backup check |
| `TenantModelTest.php` | 7 | Model Tenant, HeaderTenantFinder |
| `TenantRegistrationTest.php` | 6 | Fluxo POST /register |
| `TenantTest.php` | 6 | Isolamento de tenant, middleware, cross-tenant |
| `PasswordResetTest.php` | 5 | Forgot/reset password |
| `EmailVerificationTest.php` | 6 | Verify/resend, login bloqueado |
| `UserInviteTest.php` | 6 | Admin invite, aceitar convite |
| `PortugueseLocalizationTest.php` | 6 | Locale pt_BR |
| `ExampleTest.php` | 1 | Sanity check |

---

## Testes de Arquitetura (ArchTest)

Definidos em `tests/ArchTest.php` usando `pestphp/pest-plugin-arch`. Garantem que as convenções arquiteturais sejam seguidas automaticamente:

```php
// DTOs do domínio Auth devem estender Spatie Data
arch('Auth DTOs extend Spatie LaravelData')
    ->expect('App\\Domain\\Auth\\Data')
    ->toExtend(Data::class);

// Services devem ter sufixo Service e método handle()
arch('Domain services have a handle method')
    ->expect('App\\Domain\\*\\Services')
    ->classes()
    ->toHaveSuffix('Service')
    ->toHaveMethod('handle');

// Controllers não devem usar helper response() diretamente
arch('Controllers do not use response() helper directly')
    ->expect('App\\Http\\Controllers')
    ->not->toUse('response');

// Models devem ser classes
arch('App\\Models classes are proper classes')
    ->expect('App\\Models')
    ->toBeClasses();
```

---

## Infraestrutura (Docker / Sail)

Definida em `compose.yaml`:

| Serviço | Imagem | Porta local | Propósito |
|---------|--------|-------------|-----------|
| `laravel.test` | PHP 8.5 custom | 80 | Aplicação Laravel |
| `mysql` | mysql:8.4 | 3306 | Banco principal |
| `redis` | redis:alpine | 6379 | Cache + Queues |
| `mailpit` | axllent/mailpit | 1025 (SMTP), 8025 (UI) | E-mail local |
| `minio` | minio/minio | 9000 (API), 9001 (UI) | S3-compatible para backups e mídia |

Todos os serviços na rede `sail` (bridge). Volumes persistentes para mysql, redis e minio.

---

## Variáveis de Ambiente

```env
# Aplicação
APP_NAME=Laravel
APP_ENV=local                        # local | staging | production
APP_KEY=                             # gerado com artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

# Admin (seeders)
ADMIN_EMAIL=admin@boirplate.test
ADMIN_PASSWORD=password
ADMIN_TENANT_NAME="Default Tenant"
ADMIN_TENANT_SLUG=default

# Banco de dados (Docker/Sail)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Cache / Session / Queue
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# E-mail — local (Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@boirplate.test"
MAIL_FROM_NAME="${APP_NAME}"

# E-mail — produção (Resend)
# MAIL_MAILER=resend
# RESEND_API_KEY=re_xxxx

# S3 / MinIO — local
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=backups
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# S3 — produção
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=
# AWS_BUCKET=
# AWS_USE_PATH_STYLE_ENDPOINT=false

# Mídia
MEDIA_DISK=public                    # produção: s3

# Backup
BACKUP_NOTIFICATION_EMAIL=admin@example.com

# Health
HEALTH_NOTIFICATIONS_ENABLED=true
HEALTH_TO_ADDRESS=
HEALTH_SECRET_TOKEN=                 # token opcional para proteger /health

# Discord (alertas opcionais)
DISCORD_WEBHOOK_URL=
```

---

## Serviços e dashboards locais

| Serviço | URL | Credenciais | Notas |
|---------|-----|-------------|-------|
| API | `http://localhost/api/v1` | — | Base de todos os endpoints |
| Docs OpenAPI | `http://localhost/docs/api` | — | Auto-gerado pelo Scramble |
| Health | `http://localhost/health` | — | Público |
| Horizon | `http://localhost/horizon` | role: admin | Monitor de filas |
| Pulse | `http://localhost/pulse` | role: admin | Monitoramento de performance |
| Telescope | `http://localhost/telescope` | role: admin | Debug (local only) |
| Mailpit | `http://localhost:8025` | — | E-mails capturados localmente |
| MinIO | `http://localhost:9001` | sail / password | S3 local para backups e mídia |
| Reverb | `ws://localhost:8080` | — | WebSocket server (local) |

---

## Como adicionar um novo domínio

Exemplo completo: domínio `Product`.

### 1. Migration

```bash
vendor/bin/sail artisan make:migration create_products_table --no-interaction
```

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->text('description')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index('tenant_id');
});
```

### 2. Model

```php
// app/Models/Product.php
declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = ['name', 'price', 'description'];
    protected $hidden   = ['id', 'tenant_id', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'price'      => 'float',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
```

### 3. DTO

```php
// app/Domain/Product/Data/CreateProductData.php
declare(strict_types=1);

namespace App\Domain\Product\Data;

use Spatie\LaravelData\Data;

class CreateProductData extends Data
{
    public function __construct(
        public readonly string  $name,
        public readonly float   $price,
        public readonly ?string $description,
    ) {}
}
```

### 4. Service

```php
// app/Domain/Product/Services/CreateProductService.php
declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\Data\CreateProductData;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductService
{
    public function handle(CreateProductData $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'name'        => $data->name,
                'price'       => $data->price,
                'description' => $data->description,
            ]);

            return $product->fresh();
        }, 3);
    }
}
```

### 5. Form Request

```php
// app/Http/Requests/Api/V1/Dashboard/Product/CreateProductRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O campo nome é obrigatório.',
            'price.required' => 'O campo preço é obrigatório.',
            'price.numeric'  => 'O preço deve ser um número.',
        ];
    }
}
```

### 6. Resource

```php
// app/Http/Resources/Api/V1/Dashboard/Product/ProductResource.php
declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'price'       => $this->price,
            'description' => $this->description,
            'created_at'  => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
```

### 7. Controller

```php
// app/Http/Controllers/Api/V1/Dashboard/ProductController.php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domain\Product\Data\CreateProductData;
use App\Domain\Product\Services\CreateProductService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Product\CreateProductRequest;
use App\Http\Resources\Api\V1\Dashboard\Product\ProductResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Products
 */
class ProductController extends ApiController
{
    public function store(CreateProductRequest $request, CreateProductService $service): JsonResponse
    {
        $data    = CreateProductData::from($request->validated());
        $product = $service->handle($data);

        return $this->created(new ProductResource($product), 'Produto criado com sucesso.');
    }
}
```

### 8. Rota

```php
// routes/api.php — dentro do grupo ensure_tenant + auth:sanctum
Route::post('products', [ProductController::class, 'store']);
// ou para CRUD completo:
Route::apiResource('products', ProductController::class);
```

### 9. Teste

```php
// tests/Feature/ProductTest.php
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    $this->user = User::factory()->create();
    tenantActingAs($this->user);
});

it('creates a product', function () {
    $this
        ->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/products', [
            'name'  => 'Produto Teste',
            'price' => 99.90,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Produto Teste');
});
```

---

## Convenções de código

### Obrigatórias

- `declare(strict_types=1)` no topo de todos os arquivos em `app/`
- Usar `DB::transaction(fn() => ..., 3)` em toda escrita no banco
- Chamar `$model->fresh()` antes de retornar após write em transaction
- Notificações/e-mails **fora** do closure de transaction
- Nunca usar `response()->json()` em controllers — usar a trait `ApiResponse`
- Nunca usar `try/catch` que só faz `report()` + rethrow
- Sempre usar Form Requests com `authorize(): bool { return true; }`
- Mensagens de validação sempre em português
- Route binding sempre via `uuid`
- Rodar `vendor/bin/sail bin pint --dirty --format agent` após qualquer mudança PHP
- Casts declarados no método `casts()`, nunca na propriedade `$casts`
- Type hints explícitos em todos os parâmetros e retornos de método

### Estrutura canônica de service

```php
declare(strict_types=1);

namespace App\Domain\{Dominio}\Services;

use Illuminate\Support\Facades\DB;

class {Nome}Service
{
    public function handle({Nome}Data $data): SomeModel
    {
        $result = DB::transaction(function () use ($data): SomeModel {
            $model = SomeModel::create([...]);

            return $model->fresh();
        }, 3);

        // efeitos colaterais (emails, eventos) aqui, fora da transaction
        $result->notify(new SomeNotification());

        return $result;
    }
}
```

### Estrutura canônica de teste

```php
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

    $this->user = User::factory()->create();
    tenantActingAs($this->user);
});

it('does something', function () {
    $this
        ->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/endpoint', [...])
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.field', 'value');
});
```
