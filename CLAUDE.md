# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

**Project root:** `/Users/luis/code/projetos/boirplate`

## Commands

```bash
# Start/stop services
vendor/bin/sail up -d
vendor/bin/sail stop

# Run all tests
vendor/bin/sail artisan test --compact

# Run a single test file or filter
vendor/bin/sail artisan test --compact --filter=AuthTest

# Create a Pest test
vendor/bin/sail artisan make:test --pest FeatureName --no-interaction

# Run database migrations
vendor/bin/sail artisan migrate --no-interaction

# Seed the database
vendor/bin/sail artisan db:seed --no-interaction

# Format PHP code (run after EVERY PHP change — mandatory)
vendor/bin/sail bin pint --dirty --format agent

# Clear caches
vendor/bin/sail artisan optimize:clear

# Dev server (serve + queue + pail + vite via concurrently)
vendor/bin/sail composer run dev
```

> **Note:** All `sail` commands run from the project root `/Users/luis/code/projetos/boirplate`.

## Architecture

### Domain-Driven Structure

Business logic lives in `app/Domain/{Domain}/` organized into:
- `Services/` — single-action classes with `handle()` method; one class per operation
- `Data/` — DTOs extending `Spatie\LaravelData\Data`

### API Layer

Controllers live under `app/Http/Controllers/Api/V1/Dashboard/` and extend `ApiController` (uses `ApiResponse` trait).

**Request flow:** `FormRequest → DTO::from($request->validated()) → Service::handle() → Resource → ApiResponse`

- Form Requests: `app/Http/Requests/Api/V1/Dashboard/{Resource}/`
- Resources: `app/Http/Resources/Api/V1/Dashboard/{Resource}/`

### Models

- All models use `HasUuid` trait; route model binding uses `uuid`.
- `id` and `deleted_at` are hidden from all API responses.
- Casts defined in `casts()` method (not `$casts` property).
- Enum casts use `App\Enums\*Enum`; all enums expose `values(): array`.
- Models with file uploads implement `HasMedia` + `InteractsWithMedia` (spatie/laravel-medialibrary).
- Models requiring change tracking implement `Auditable` (owen-it/laravel-auditing).
- `declare(strict_types=1)` at the top of every app/ PHP file.

### Service Patterns

- Single-action classes with `handle()` method.
- Database writes use `DB::transaction(fn() => ..., 3)` — the `3` retries on deadlock.
- Call `$model->fresh()` before returning after a write.
- Do NOT wrap code in `try/catch` that only calls `report()` + rethrow — Laravel handles this automatically.

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
        public readonly string $password, // mark sensitive fields Hidden
    ) {}
}
```

- No manual `from()` method needed — inherited from `Data`.
- Use `#[Hidden]` on sensitive fields (e.g. passwords) to exclude from serialization.
- Call via `CreateSomethingData::from($request->validated())`.

### RBAC (spatie/laravel-permission)

- Guard: `sanctum` (configured in `config/permission.php` and `config/auth.php`).
- Roles: `admin`, `user` (created by `RoleSeeder`).
- `User` model uses `HasRoles` trait.
- Middleware aliases registered in `bootstrap/app.php`: `role`, `permission`, `role_or_permission`.
- Protected routes use `middleware(['auth:sanctum', 'role:admin'])`.
- Gates in `HorizonServiceProvider` and `TelescopeServiceProvider` use `$user->hasRole('admin')`.

### Media / File Uploads (spatie/laravel-medialibrary)

- Models with uploads implement `HasMedia`, use `InteractsWithMedia`.
- Define collections in `registerMediaCollections()` and conversions in `registerMediaConversions()` as separate methods.
- Use `->singleFile()` on collections that replace (e.g. avatar).
- Use `->nonQueued()` on conversions that must be synchronous.
- Default disk: `public`. Switch to S3 via `MEDIA_DISK=s3` in `.env`.

### Response Format

Use `ApiResponse` trait methods — never `response()->json()` directly:
- `$this->success(data, message, code, meta)`
- `$this->created(data, message)`
- `$this->paginated($paginator, message)` — pagination meta included automatically
- `$this->noContent()`
- `$this->error()`, `$this->notFound()`, `$this->forbidden()`, `$this->unauthorized()`, `$this->validationError()`

Response envelope: `{ success, message, data, meta? }`.

### Validation

- Always use Form Requests with `authorize()` returning `true`.
- Validation messages in Portuguese — never switch to English.

### List Services & Filtering

Use `Spatie\QueryBuilder\QueryBuilder` for filtering/sorting. Return via `$this->paginated()`.

### Export Pattern

Use `spatie/simple-excel` for CSV/XLSX.

### Testing Conventions

- Authenticate with `Sanctum::actingAs(User::factory()->create())`.
- Use a local `userPayload(array $overrides = [])` helper for reusable payloads.
- Tests use `RefreshDatabase`, live under `tests/Feature/Api/V1/Dashboard/`.
- Role tests: create roles in `beforeEach` with `Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum'])`.
- Use `User::factory()->inactive()` for inactive user scenarios.
- Media tests: `Storage::fake('public')` + `UploadedFile::fake()->image(...)`.

## Infrastructure

| Service | URL | Purpose |
|---------|-----|---------|
| App | `http://localhost` | Laravel API |
| Mailpit | `http://localhost:8025` | Local email dashboard |
| Horizon | `http://localhost/horizon` | Queue monitor |
| Telescope | `http://localhost/telescope` | Debug (local only) |
| Health | `http://localhost/health` | Health checks (DB, Redis, Horizon, Queue) |
| API Docs | `http://localhost/docs/api` | Auto-generated OpenAPI |

## Key Packages

| Package | Purpose |
|---------|---------|
| `spatie/laravel-data` | DTOs — extend `Data`, no manual `from()` |
| `spatie/laravel-permission` | RBAC — roles/permissions, guard: `sanctum` |
| `spatie/laravel-medialibrary` | File uploads — collections, conversions |
| `spatie/laravel-health` | Health endpoint at `/health` |
| `spatie/laravel-query-builder` | Filtering/sorting in list services |
| `spatie/simple-excel` | CSV/XLSX import/export |
| `laravel/sanctum` | API token auth |
| `dedoc/scramble` | Auto OpenAPI docs at `/docs/api` |
| `laravel/horizon` | Queue dashboard at `/horizon` |
| `laravel/telescope` | Debug dashboard (local only) |
| `owen-it/laravel-auditing` | Model change tracking |
| `resend/resend-laravel` | Transactional email |
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
