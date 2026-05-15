# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Commands

```bash
# Start/stop services (always run from project root)
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail up -d"
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail stop"

# Run all tests
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan test --compact"

# Run a single test file or filter
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan test --compact --filter=AuthTest"

# Create a Pest test
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan make:test --pest FeatureName --no-interaction"

# Run database migrations
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan migrate"

# Seed the database
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan db:seed"

# Format PHP code (run after every PHP change)
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail bin pint --dirty --format agent"

# Clear caches
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail artisan optimize:clear"

# Dev server (serve + queue + pail + vite via concurrently)
bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail composer run dev"
```

> **Note:** All `sail` commands must be run from the project root directory.
> Use `bash -c "cd ~/code/boirplates/laravel-api && vendor/bin/sail ..."` or `cd` first.

## Architecture

### Domain-Driven Structure

Business logic lives in `app/Domain/{Domain}/` organized into:
- `Services/` — single-action classes (`handle()` method); one per operation
- `Data/` — readonly DTOs with a static `from(array $data): self` constructor

### API Layer

Controllers are grouped under `app/Http/Controllers/Api/V1/Dashboard/`.

All controllers extend `ApiController` (which uses the `ApiResponse` trait).

Controller request flow: `FormRequest → DTO (Data::from($request->validated())) → Service::handle() → Resource → ApiResponse`.

- Form Requests: `app/Http/Requests/Api/V1/Dashboard/{Resource}/`
- Resources: `app/Http/Resources/Api/V1/Dashboard/{Resource}/`

### Models

- All models use the `HasUuid` trait; route model binding uses `uuid`.
- `id` and `deleted_at` are hidden; UUIDs are exposed in all API responses.
- Casts are defined in a `casts()` method, not a `$casts` property.
- Enum casts use `App\Enums\*Enum` classes; all enums expose a static `values(): array` helper.
- Add `declare(strict_types=1);` to new files.

### Service Patterns

- Single-action classes with `handle()` method.
- Database writes use `DB::transaction(fn() => ..., 3)` — the `3` enables automatic retry on deadlock.
- Call `$model->fresh()` before returning after a write.
- Errors: `report($exception)` then rethrow. Never swallow silently.

### DTO Pattern

```php
readonly class CreateSomethingData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
        );
    }
}
```

### Response Format

Use methods from the `ApiResponse` trait — never return `response()->json()` directly:
- `$this->success(data, message, code, meta)`
- `$this->created(data, message)`
- `$this->paginated($paginator, message)` — includes pagination meta automatically
- `$this->noContent()`
- `$this->error()`, `$this->notFound()`, `$this->forbidden()`, `$this->unauthorized()`, `$this->validationError()`

Response envelope shape: `{ success, message, data, meta? }`.

### Validation

- Always use Form Requests with `authorize()` returning `true`.
- Keep validation messages in Portuguese — do not switch to English.

### List Services & Filtering

Use **Spatie Query Builder** (`Spatie\QueryBuilder\QueryBuilder`) for filtering and sorting in list services. Results are paginated and returned via `$this->paginated()`.

### Export Pattern

Use `spatie/simple-excel` for reading and writing CSV/XLSX files.

### Auditing

Models that require change tracking implement `OwenIt\Auditing\Contracts\Auditable` and use the `OwenIt\Auditing\Auditable` trait.

### Testing Conventions

- **API tests**: authenticate with `Sanctum::actingAs(User::factory()->create())`.
- Use a local helper function (e.g. `userPayload(array $overrides = [])`) for reusable request payloads.
- Tests use `RefreshDatabase` and live under `tests/Feature/Api/V1/Dashboard/`.
- Use `User::factory()->inactive()` state to test inactive user scenarios.

## Key Packages

- **spatie/simple-excel** — import/export CSV/XLSX
- **spatie/laravel-query-builder** — filtering/sorting in list services
- **laravel/sanctum** — API token authentication
- **dedoc/scramble** — automatic OpenAPI docs at `/docs/api`
- **laravel/horizon** — queue dashboard at `/horizon`
- **laravel/telescope** — debug dashboard at `/telescope` (local only)
- **owen-it/laravel-auditing** — model change tracking
- **resend/resend-laravel** — transactional email
- **spatie/laravel-discord-alerts** — Discord webhook alerts
- **barryvdh/laravel-ide-helper** — IDE autocomplete helpers
