# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

**Project root:** `/Users/luis/code/projetos/boirplate`
**Stack:** Laravel 13, PHP 8.5, MySQL 8.4, Redis, Sail/Docker, Pest v4
**Type:** SaaS API boilerplate — multi-tenant, domain-driven, API-first, pt_BR

---

## Commands

```bash
# Start/stop services
vendor/bin/sail up -d
vendor/bin/sail stop

# Run all tests (66 tests)
vendor/bin/sail artisan test --compact

# Run a single test file or filter
vendor/bin/sail artisan test --compact --filter=AuthTest

# Create a Pest test
vendor/bin/sail artisan make:test --pest FeatureName --no-interaction

# Run database migrations
vendor/bin/sail artisan migrate --no-interaction

# Seed the database (RoleSeeder → TenantSeeder → UserSeeder)
vendor/bin/sail artisan db:seed --no-interaction

# Format PHP code (run after EVERY PHP change — mandatory)
vendor/bin/sail bin pint --dirty --format agent

# Clear caches
vendor/bin/sail artisan optimize:clear

# Run backup manually
vendor/bin/sail artisan backup:run --no-interaction

# Dev server
vendor/bin/sail composer run dev
```

---

## Architecture

### Domain-Driven Structure

```
app/Domain/{Domain}/
  Services/   — single-action classes, one per operation, handle() method
  Data/       — DTOs extending Spatie\LaravelData\Data
app/Http/Controllers/Api/V1/Dashboard/
  AuthController.php          — all auth endpoints
  Admin/AdminUserController.php — admin-only endpoints
app/Models/                   — Tenant, User, UserInvitation
app/Traits/                   — HasUuid, ApiResponse, BelongsToTenant
app/Enums/                    — TenantStatusEnum, UserStatusEnum
app/Notifications/Auth/       — ForgotPasswordNotification, InviteUserNotification, VerifyEmailNotification
```

**Request flow:** `FormRequest → DTO::from($request->validated()) → Service::handle() → Resource → ApiResponse`

- Form Requests: `app/Http/Requests/Api/V1/Dashboard/{Resource}/`
- Resources: `app/Http/Resources/Api/V1/Dashboard/{Resource}/`

### Multi-Tenancy (spatie/laravel-multitenancy)

- **Single database** — all tenant-scoped tables have a `tenant_id` FK.
- **Identification:** `X-Tenant-ID` header (UUID) on every API request except `POST /api/v1/register`.
- **Middleware:** `EnsureTenant` (`app/Http/Middleware/EnsureTenant.php`) — resolves tenant from header via `HeaderTenantFinder`, calls `$tenant->makeCurrent()`, returns 422 JSON if not found/inactive.
- **Trait:** `BelongsToTenant` (`app/Traits/BelongsToTenant.php`) — adds global scope `WHERE tenant_id = current` and auto-sets `tenant_id` on model creation. Custom trait (package v4 doesn't ship it).
- **Tenant migrations** live in `database/migrations/landlord/` and are loaded via `AppServiceProvider::boot()`.
- `Tenant::current()` is always set after `EnsureTenant` runs.

### Models

**Shared rules for all models:**
- `HasUuid` trait — auto-generates uuid on create; route binding uses `uuid`.
- `id`, `tenant_id`, `deleted_at` hidden from API responses.
- Casts in `casts()` method (never `$casts` property).
- `declare(strict_types=1)` at the top of every app/ PHP file.

**`Tenant`** (`app/Models/Tenant.php`):
- Extends `Spatie\Multitenancy\Models\Tenant`
- Traits: `HasUuid`, `SoftDeletes`, `HasFactory`
- Casts: `status → TenantStatusEnum`
- Fields: `uuid`, `name`, `slug` (unique), `status`

**`User`** (`app/Models/User.php`):
- Implements: `Auditable`, `HasMedia`, `MustVerifyEmailContract`
- Traits: `BelongsToTenant`, `HasApiTokens`, `HasRoles`, `HasUuid`, `InteractsWithMedia`, `MustVerifyEmail`, `Notifiable`, `Auditable`, `SoftDeletes`
- Casts: `email_verified_at → datetime`, `password → hashed`, `status → UserStatusEnum`
- Media collection: `avatar` (singleFile, thumb 150×150 nonQueued)
- `sendEmailVerificationNotification()` overridden to use `VerifyEmailNotification`
- Status values: `active`, `inactive`, `pending` (pending = invited, hasn't set password yet)

**`UserInvitation`** (`app/Models/UserInvitation.php`):
- Fields: `tenant_id`, `user_id`, `token` (UUID, unique), `expires_at`, `accepted_at`
- Relations: `user()`, `tenant()`

### Service Patterns

- Single-action classes with `handle()` method.
- Database writes: `DB::transaction(fn() => ..., 3)` — retries 3x on deadlock.
- Call `$model->fresh()` before returning after a write.
- Dispatch notifications/emails **outside** the transaction closure (side effects don't belong in transactions).
- Never `try/catch` that only calls `report()` + rethrow — Laravel handles automatically.

### DTO Pattern (spatie/laravel-data)

```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Hidden;

class CreateSomethingData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password, // sensitive fields get #[Hidden]
    ) {}
}
```

- No manual `from()` — inherited from `Data`.
- Call via `CreateSomethingData::from($request->validated())`.

### RBAC (spatie/laravel-permission)

- Guard: `sanctum` (in `config/permission.php` and `config/auth.php`).
- Roles: `admin`, `user` — created by `RoleSeeder` with `guard_name = 'sanctum'`.
- Traits: `HasRoles` on `User` model.
- Middleware aliases: `role`, `permission`, `role_or_permission` (in `bootstrap/app.php`).
- Protected routes: `middleware(['auth:sanctum', 'role:admin'])`.
- Horizon/Telescope gates: `$user->hasRole('admin')`.

### Auth Flows

All routes require `X-Tenant-ID` header unless noted.

| Endpoint | Middleware | Description |
|----------|-----------|-------------|
| `POST /api/v1/register` | **public** | Create new tenant + admin user atomically |
| `POST /api/v1/login` | ensure_tenant, throttle:auth | Login (blocks unverified + inactive users) |
| `POST /api/v1/logout` | ensure_tenant, auth:sanctum | Revoke Sanctum token |
| `GET /api/v1/me` | ensure_tenant, auth:sanctum | Authenticated user data |
| `POST /api/v1/forgot-password` | ensure_tenant, throttle:auth | Send reset email (no user enumeration) |
| `POST /api/v1/reset-password` | ensure_tenant, throttle:auth | Reset password (token hashed SHA-256, 1h expiry, auto-verifies email) |
| `GET /api/v1/email/verify/{uuid}/{hash}` | ensure_tenant | Verify email (signed URL, 60min, named `verification.verify`) |
| `POST /api/v1/email/resend` | ensure_tenant, auth:sanctum | Resend verification email |
| `POST /api/v1/admin/users/invite` | ensure_tenant, auth:sanctum, role:admin | Create pending user + send invite (24h token) |
| `POST /api/v1/invite/accept` | ensure_tenant | Accept invite → set password + verify email |

**Key behaviors:**
- Login blocks `email_verified_at = null` users.
- `password_reset_tokens` is tenant-scoped (`(email, tenant_id)` composite PK).
- Email verification URL uses `uuid` (not integer id) in the signed route.
- Accepting an invite simultaneously verifies the email and activates the account.
- `RegisterTenantService` sends verification email after creating the user (outside transaction).

### Media / File Uploads (spatie/laravel-medialibrary)

- Models: implement `HasMedia`, use `InteractsWithMedia`.
- `registerMediaCollections()` and `registerMediaConversions()` are separate methods.
- Use `->singleFile()` for replace-on-upload collections (e.g. avatar).
- Use `->nonQueued()` for synchronous conversions.
- Default disk: `public`. Production: `MEDIA_DISK=s3`.

### Response Format

Always use `ApiResponse` trait — never `response()->json()`:
- `$this->success(data, message, code, meta)`
- `$this->created(data, message)`
- `$this->paginated($paginator, message)` — pagination meta auto-included
- `$this->noContent()`
- `$this->error()`, `$this->notFound()`, `$this->forbidden()`, `$this->unauthorized()`, `$this->validationError()`

Envelope: `{ success, message, data, meta? }`.

### Validation

- Always use Form Requests with `authorize()` returning `true`.
- Messages in Portuguese — never English.
- Email uniqueness within tenant: `Rule::unique('users')->where(fn($q) => $q->where('tenant_id', Tenant::current()->getKey()))`.

### List Services & Filtering

Use `Spatie\QueryBuilder\QueryBuilder`. Return via `$this->paginated()`.

### Export

Use `spatie/simple-excel` for CSV/XLSX.

### Backup (spatie/laravel-backup)

- MySQL dump → zip → S3 (`backups` bucket on MinIO locally).
- Schedule: `backup:run` at 06:00 and 23:00, `backup:clean` at 00:30.
- Retention: 7 daily + 4 weekly + 3 monthly + 1 yearly.
- Notifications on failure: email to `BACKUP_NOTIFICATION_EMAIL`.
- Config: `config/backup.php`. No file backup — database only, no zip encryption.

### Testing Conventions

**Multi-tenancy setup (required in all feature tests that touch Users):**
```php
beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});
```

**Helper functions (defined in `tests/Pest.php`):**
- `tenantActingAs(User $user): User` — calls `$user->tenant->makeCurrent()` + `Sanctum::actingAs($user)`.

**All HTTP requests to tenant routes need the header:**
```php
$this->withHeader('X-Tenant-ID', $this->tenant->uuid)->postJson(...)
```

**Factory states:**
- `User::factory()->create()` — active + verified (default)
- `User::factory()->inactive()` — status: inactive
- `User::factory()->unverified()` — email_verified_at: null
- `User::factory()->pending()` — status: pending, no password, unverified
- `Tenant::factory()->inactive()` — status: inactive
- `UserInvitation::factory()->expired()` — expires_at in the past
- `UserInvitation::factory()->accepted()` — accepted_at set

**Role setup in tests that use roles:**
```php
beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
});
```

**Email/notification tests:** use `Notification::fake()` and assert with `Notification::assertSentTo(...)`.

**Current test files (66 tests):**

| File | Tests | What it covers |
|------|-------|----------------|
| `AuthTest.php` | 9 | Login, logout, /me, missing header |
| `RoleTest.php` | 4 | Role assignment, role:admin middleware |
| `AvatarTest.php` | 3 | Media upload, singleFile |
| `HealthTest.php` | 4 | /health endpoint, DB check, Redis check |
| `BackupTest.php` | 3 | backup:clean, health includes Backup check |
| `TenantModelTest.php` | 7 | Tenant model, HeaderTenantFinder |
| `TenantRegistrationTest.php` | 6 | POST /register flow |
| `TenantTest.php` | 6 | Tenant isolation, middleware, cross-tenant |
| `PasswordResetTest.php` | 5 | Forgot/reset password |
| `EmailVerificationTest.php` | 6 | Verify/resend, login blocking |
| `UserInviteTest.php` | 6 | Admin invite, accept invite |
| `PortugueseLocalizationTest.php` | 6 | pt_BR locale |
| `ExampleTest.php` | 1 | Sanity check |

---

## Infrastructure

| Service | URL | Credentials | Purpose |
|---------|-----|-------------|---------|
| App | `http://localhost` | — | Laravel API |
| Mailpit | `http://localhost:8025` | — | Local email dashboard |
| MinIO | `http://localhost:9001` | sail / password | S3-compatible storage for backups |
| Horizon | `http://localhost/horizon` | admin role | Queue monitor |
| Telescope | `http://localhost/telescope` | admin role | Debug (local only) |
| Health | `http://localhost/health` | public | DB, Redis, Horizon, Queue, Backup |
| API Docs | `http://localhost/docs/api` | — | Auto-generated OpenAPI (Scramble) |

---

## Database Schema (tables)

| Table | Purpose |
|-------|---------|
| `tenants` | Tenant registry (in `migrations/landlord/`) |
| `users` | Users — `tenant_id` FK, `email_verified_at`, composite unique `(email, tenant_id)` |
| `user_invitations` | Pending invites — `token` (UUID), `expires_at`, `accepted_at` |
| `password_reset_tokens` | Reset tokens — composite PK `(email, tenant_id)`, token stored as SHA-256 hash |
| `personal_access_tokens` | Sanctum tokens |
| `roles`, `permissions`, `model_has_roles`, etc. | Spatie permission tables (guard: sanctum) |
| `media` | Spatie media library |
| `audits` | Model change tracking |
| `telescope_entries` | Telescope debug data |
| `health_check_result_history_items` | Spatie health history |
| `sessions`, `cache`, `jobs` | Standard Laravel tables |

**Seeders order:** `RoleSeeder` → `TenantSeeder` → `UserSeeder`

Admin user credentials from env: `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_TENANT_SLUG`.

---

## Key Packages

| Package | Purpose |
|---------|---------|
| `spatie/laravel-data` | DTOs — extend `Data`, `#[Hidden]` for sensitive fields |
| `spatie/laravel-permission` | RBAC — guard: `sanctum`, roles: admin/user |
| `spatie/laravel-medialibrary` | File uploads — collections + conversions |
| `spatie/laravel-multitenancy` | Multi-tenancy — X-Tenant-ID header, BelongsToTenant |
| `spatie/laravel-health` | `/health` — DB, Redis, Horizon, Queue, Backup checks |
| `spatie/laravel-backup` | Automated MySQL backup → MinIO/S3 |
| `spatie/laravel-query-builder` | Filtering/sorting in list services |
| `spatie/simple-excel` | CSV/XLSX import/export |
| `laravel/sanctum` | API token authentication |
| `dedoc/scramble` | Auto OpenAPI docs at `/docs/api` |
| `laravel/horizon` | Queue dashboard at `/horizon` |
| `laravel/telescope` | Debug dashboard (local only) |
| `owen-it/laravel-auditing` | Model change tracking |
| `resend/resend-laravel` | Transactional email (production) |
| `spatie/laravel-discord-alerts` | Discord webhook alerts |
| `barryvdh/laravel-ide-helper` | IDE autocomplete |

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/boost (BOOST) - v2
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/telescope (TELESCOPE) - v5
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
