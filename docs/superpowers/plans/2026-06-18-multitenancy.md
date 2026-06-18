# Multi-Tenancy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add single-database multi-tenancy to the Laravel 13 API boilerplate using `spatie/laravel-multitenancy`, tenant identification via `X-Tenant-ID` header, and a public registration endpoint that creates a tenant + admin user atomically.

**Architecture:** Each tenant row lives in a `tenants` table; all tenant-scoped models carry a `tenant_id` FK resolved by a custom `HeaderTenantFinder`. A custom `EnsureTenant` middleware resolves the current tenant from the header before every protected route; the registration route is the only public (no-tenant) endpoint.

**Tech Stack:** Laravel 13, PHP 8.5, `spatie/laravel-multitenancy` v3, `spatie/laravel-data`, Pest v4, Sail/MySQL.

## Global Constraints

- All commands: `vendor/bin/sail <cmd>` from `/Users/luis/code/projetos/boirplate`
- `declare(strict_types=1)` at the top of every new app/ PHP file
- `vendor/bin/sail bin pint --dirty --format agent` after every PHP change
- Tests: `vendor/bin/sail artisan test --compact`
- Validation messages in Portuguese
- Route model binding uses `uuid`; `id` hidden from all API responses
- Response envelope: `{ success, message, data, meta? }` — always use `ApiResponse` trait methods
- Every new DTO extends `Spatie\LaravelData\Data`; sensitive fields use `#[Hidden]`
- Permission guard: `sanctum`

---

### Task 1: Package + TenantStatusEnum + Tenant model/migration/factory/seeder

**Files:**
- Create: `app/Enums/TenantStatusEnum.php`
- Create: `app/Models/Tenant.php`
- Create: `database/migrations/*_create_tenants_table.php` (published, then customized)
- Create: `database/factories/TenantFactory.php`
- Create: `database/seeders/TenantSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `.env.example`

**Interfaces:**
- Produces: `Tenant::factory()->create()`, `Tenant::factory()->inactive()->create()`, `TenantStatusEnum`, `$tenant->makeCurrent()`, `Tenant::current()`

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-multitenancy
```

- [ ] **Step 2: Publish config and migration**

```bash
vendor/bin/sail artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-migrations" --no-interaction
vendor/bin/sail artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-config" --no-interaction
```

- [ ] **Step 3: Customize the published tenants migration**

Find the file `database/migrations/*_create_tenants_table.php` and replace its content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

- [ ] **Step 4: Create `TenantStatusEnum`**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

Save to `app/Enums/TenantStatusEnum.php`.

- [ ] **Step 5: Create `Tenant` model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatusEnum;
use App\Traits\HasUuid;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatusEnum::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
```

Save to `app/Models/Tenant.php`.

- [ ] **Step 6: Update `config/multitenancy.php` — set model and disable connection switching**

Open the published `config/multitenancy.php` and change these two values:

```php
'tenant_model' => \App\Models\Tenant::class,

'switch_tenant_tasks' => [
    // Empty — single database, no connection switching needed
],
```

- [ ] **Step 7: Create `TenantFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
```

Save to `database/factories/TenantFactory.php`.

- [ ] **Step 8: Create `TenantSeeder`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => env('ADMIN_TENANT_SLUG', 'default')],
            [
                'name' => env('ADMIN_TENANT_NAME', 'Default Tenant'),
                'status' => 'active',
            ]
        );

        $tenant->makeCurrent();
    }
}
```

Save to `database/seeders/TenantSeeder.php`.

- [ ] **Step 9: Update `DatabaseSeeder` — call TenantSeeder before UserSeeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
        ]);
    }
}
```

- [ ] **Step 10: Add env vars to `.env.example`**

Add after the `ADMIN_PASSWORD` lines:

```dotenv
ADMIN_TENANT_NAME="Default Tenant"
ADMIN_TENANT_SLUG=default
```

- [ ] **Step 11: Run migration**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 12: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 13: Write failing test for Tenant model**

```bash
vendor/bin/sail artisan make:test --pest TenantModelTest --no-interaction
```

Replace `tests/Feature/TenantModelTest.php`:

```php
<?php

use App\Enums\TenantStatusEnum;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a tenant with uuid automatically', function () {
    $tenant = Tenant::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

    expect($tenant->uuid)->not->toBeEmpty();
    expect($tenant->name)->toBe('Acme Corp');
    expect($tenant->slug)->toBe('acme-corp');
    expect($tenant->status)->toBe(TenantStatusEnum::Active);
});

it('casts status to TenantStatusEnum', function () {
    $tenant = Tenant::factory()->create();
    expect($tenant->status)->toBeInstanceOf(TenantStatusEnum::class);
});

it('can set current tenant and retrieve it', function () {
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    expect(Tenant::current()->uuid)->toBe($tenant->uuid);
});

it('creates inactive tenant via factory state', function () {
    $tenant = Tenant::factory()->inactive()->create();
    expect($tenant->status)->toBe(TenantStatusEnum::Inactive);
});
```

- [ ] **Step 14: Run TenantModelTest**

```bash
vendor/bin/sail artisan test --compact --filter=TenantModelTest
```

Expected: 4 tests pass.

- [ ] **Step 15: Commit**

```bash
git add app/Enums/TenantStatusEnum.php app/Models/Tenant.php database/migrations/ database/factories/TenantFactory.php database/seeders/ config/multitenancy.php .env.example tests/Feature/TenantModelTest.php composer.json composer.lock
git commit -m "feat: add Tenant model, TenantStatusEnum, and multi-tenancy foundation"
```

---

### Task 2: HeaderTenantFinder + add tenant_id to users table

**Files:**
- Create: `app/Domain/Tenant/Finders/HeaderTenantFinder.php`
- Create: `database/migrations/*_add_tenant_id_to_users_table.php`

**Interfaces:**
- Consumes: `Tenant` (Task 1), `TenantStatusEnum` (Task 1)
- Produces: `HeaderTenantFinder::findForRequest(Request): ?Tenant`

- [ ] **Step 1: Create `HeaderTenantFinder`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Finders;

use App\Enums\TenantStatusEnum;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

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

Save to `app/Domain/Tenant/Finders/HeaderTenantFinder.php`.

- [ ] **Step 2: Set `tenant_finder` in `config/multitenancy.php`**

```php
'tenant_finder' => \App\Domain\Tenant\Finders\HeaderTenantFinder::class,
```

- [ ] **Step 3: Create migration to add tenant_id to users**

```bash
vendor/bin/sail artisan make:migration add_tenant_id_to_users_table --no-interaction
```

Replace the generated migration body:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
```

- [ ] **Step 4: Run migration**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 5: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Write failing test for HeaderTenantFinder**

Add to `tests/Feature/TenantModelTest.php` (append before the closing, not inside an existing `it()`):

```php
it('HeaderTenantFinder resolves tenant from X-Tenant-ID header', function () {
    $tenant = Tenant::factory()->create();

    $request = \Illuminate\Http\Request::create('/');
    $request->headers->set('X-Tenant-ID', $tenant->uuid);

    $finder = new \App\Domain\Tenant\Finders\HeaderTenantFinder();
    $found = $finder->findForRequest($request);

    expect($found->uuid)->toBe($tenant->uuid);
});

it('HeaderTenantFinder returns null when header is missing', function () {
    $request = \Illuminate\Http\Request::create('/');

    $finder = new \App\Domain\Tenant\Finders\HeaderTenantFinder();

    expect($finder->findForRequest($request))->toBeNull();
});

it('HeaderTenantFinder returns null for inactive tenant', function () {
    $tenant = Tenant::factory()->inactive()->create();

    $request = \Illuminate\Http\Request::create('/');
    $request->headers->set('X-Tenant-ID', $tenant->uuid);

    $finder = new \App\Domain\Tenant\Finders\HeaderTenantFinder();

    expect($finder->findForRequest($request))->toBeNull();
});
```

- [ ] **Step 7: Run TenantModelTest**

```bash
vendor/bin/sail artisan test --compact --filter=TenantModelTest
```

Expected: 7 tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Domain/Tenant/Finders/HeaderTenantFinder.php config/multitenancy.php database/migrations/ tests/Feature/TenantModelTest.php
git commit -m "feat: add HeaderTenantFinder and tenant_id FK on users"
```

---

### Task 3: EnsureTenant middleware + route restructure + User BelongsToTenant + seeder update

**Files:**
- Create: `app/Http/Middleware/EnsureTenant.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/api.php`
- Modify: `app/Models/User.php`

**Interfaces:**
- Consumes: `HeaderTenantFinder` (Task 2), `ApiResponseFactory`, `Tenant::makeCurrent()`
- Produces: `EnsureTenant` middleware alias `ensure_tenant`; `User` model with `BelongsToTenant` global scope

- [ ] **Step 1: Create `EnsureTenant` middleware**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Finders\HeaderTenantFinder;
use App\Support\Api\ApiResponseFactory;
use Closure;
use Illuminate\Http\Request;

class EnsureTenant
{
    public function __construct(private readonly HeaderTenantFinder $finder) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->finder->findForRequest($request);

        if (! $tenant) {
            return ApiResponseFactory::error('Tenant não encontrado ou inativo', 422);
        }

        $tenant->makeCurrent();

        return $next($request);
    }
}
```

Save to `app/Http/Middleware/EnsureTenant.php`.

- [ ] **Step 2: Register `ensure_tenant` alias in `bootstrap/app.php`**

```php
<?php

use App\Http\Middleware\EnsureTenant;
use App\Support\Api\ApiExceptionRegister;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'ensure_tenant' => EnsureTenant::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionRegister::register($exceptions);
    })->create();
```

- [ ] **Step 3: Restructure `routes/api.php`**

```php
<?php

use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public — no tenant required
    Route::post('register', [AuthController::class, 'register']);

    // All routes below require a valid X-Tenant-ID header
    Route::middleware('ensure_tenant')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Admin-only routes
        Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
            // Route::apiResource('users', UserController::class);
        });
    });
});
```

- [ ] **Step 4: Add `BelongsToTenant` trait to `User` model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatusEnum;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Multitenancy\Concerns\BelongsToTenant;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use BelongsToTenant;
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use InteractsWithMedia;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();
    }
}
```

- [ ] **Step 5: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Run existing tests — expect failures because tests don't have tenant context yet**

```bash
vendor/bin/sail artisan test --compact 2>&1 | head -30
```

Expected: several tests fail with "FOREIGN KEY constraint failed" or empty result sets. This is correct — they will be fixed in Task 5.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/EnsureTenant.php bootstrap/app.php routes/api.php app/Models/User.php
git commit -m "feat: add EnsureTenant middleware, restructure routes, add BelongsToTenant to User"
```

---

### Task 4: Registration endpoint

**Files:**
- Create: `app/Domain/Auth/Data/RegisterData.php`
- Create: `app/Http/Requests/Api/V1/Dashboard/Auth/RegisterRequest.php`
- Create: `app/Domain/Auth/Services/RegisterTenantService.php`
- Create: `app/Http/Resources/Api/V1/Dashboard/Tenant/TenantResource.php`
- Modify: `app/Http/Controllers/Api/V1/Dashboard/AuthController.php`

**Interfaces:**
- Consumes: `Tenant` model (Task 1), `BelongsToTenant` (Task 3), `UserStatusEnum`, `TenantStatusEnum` (Task 1)
- Produces: `POST /api/v1/register` → 201 `{ tenant, user, token }`

- [ ] **Step 1: Write the failing test first**

```bash
vendor/bin/sail artisan make:test --pest TenantRegistrationTest --no-interaction
```

Replace `tests/Feature/TenantRegistrationTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
});

function registerPayload(array $overrides = []): array
{
    return array_merge([
        'tenant_name' => 'Acme Corp',
        'tenant_slug' => 'acme-corp',
        'name' => 'João Silva',
        'email' => 'joao@acme.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

it('registers a new tenant and admin user', function () {
    $response = postJson('/api/v1/register', registerPayload());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Tenant criado com sucesso')
        ->assertJsonStructure([
            'data' => [
                'tenant' => ['uuid', 'name', 'slug', 'status', 'created_at'],
                'user' => ['uuid', 'name', 'email', 'status', 'avatar_url'],
                'token',
            ],
        ]);

    expect(Tenant::where('slug', 'acme-corp')->exists())->toBeTrue();
    expect(User::withoutGlobalScopes()->where('email', 'joao@acme.com')->exists())->toBeTrue();
});

it('assigns admin role to the registered user', function () {
    postJson('/api/v1/register', registerPayload())->assertCreated();

    $user = User::withoutGlobalScopes()->where('email', 'joao@acme.com')->first();

    expect($user->hasRole('admin'))->toBeTrue();
});

it('rejects registration with duplicate tenant slug', function () {
    Tenant::factory()->create(['slug' => 'acme-corp']);

    $response = postJson('/api/v1/register', registerPayload());

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['tenant_slug']]);
});

it('rejects registration with invalid slug format', function () {
    $response = postJson('/api/v1/register', registerPayload(['tenant_slug' => 'Acme Corp!']));

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['tenant_slug']]);
});

it('rejects registration with missing required fields', function () {
    $response = postJson('/api/v1/register', []);

    $response->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => ['tenant_name', 'tenant_slug', 'name', 'email', 'password'],
        ]);
});

it('rejects registration when passwords do not match', function () {
    $response = postJson('/api/v1/register', registerPayload(['password_confirmation' => 'different']));

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['password']]);
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=TenantRegistrationTest
```

Expected: fails with route not found or method not found on AuthController.

- [ ] **Step 3: Create `RegisterData`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(
        public readonly string $tenant_name,
        public readonly string $tenant_slug,
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
    ) {}
}
```

Save to `app/Domain/Auth/Data/RegisterData.php`.

- [ ] **Step 4: Create `RegisterRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_name.required' => 'O nome do tenant é obrigatório.',
            'tenant_name.max' => 'O nome do tenant não pode ter mais de :max caracteres.',
            'tenant_slug.required' => 'O slug do tenant é obrigatório.',
            'tenant_slug.max' => 'O slug não pode ter mais de :max caracteres.',
            'tenant_slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'tenant_slug.unique' => 'Este slug já está em uso.',
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de :max caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de :max caracteres.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
        ];
    }
}
```

Save to `app/Http/Requests/Api/V1/Dashboard/Auth/RegisterRequest.php`.

- [ ] **Step 5: Create `TenantResource`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
```

Save to `app/Http/Resources/Api/V1/Dashboard/Tenant/TenantResource.php`.

- [ ] **Step 6: Create `RegisterTenantService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\RegisterData;
use App\Enums\TenantStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterTenantService
{
    public function handle(RegisterData $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data->tenant_name,
                'slug' => $data->tenant_slug,
                'status' => TenantStatusEnum::Active,
            ]);

            $tenant->makeCurrent();

            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'status' => UserStatusEnum::Active,
            ]);

            $user->assignRole('admin');

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'tenant' => $tenant->fresh(),
                'user' => $user->fresh(),
                'token' => $token,
            ];
        }, 3);
    }
}
```

Save to `app/Domain/Auth/Services/RegisterTenantService.php`.

- [ ] **Step 7: Add `register` method to `AuthController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domain\Auth\Data\LoginUserData;
use App\Domain\Auth\Data\RegisterData;
use App\Domain\Auth\Services\LoginUserService;
use App\Domain\Auth\Services\LogoutUserService;
use App\Domain\Auth\Services\RegisterTenantService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Dashboard\Tenant\TenantResource;
use App\Http\Resources\Api\V1\Dashboard\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterTenantService $service): JsonResponse
    {
        $data = RegisterData::from($request->validated());
        $result = $service->handle($data);

        return $this->created([
            'tenant' => new TenantResource($result['tenant']),
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Tenant criado com sucesso');
    }

    public function login(LoginRequest $request, LoginUserService $service): JsonResponse
    {
        $dto = LoginUserData::from($request->validated());
        $result = $service->handle($dto);

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login realizado com sucesso');
    }

    public function logout(Request $request, LogoutUserService $service): JsonResponse
    {
        $service->handle($request->user());

        return $this->success(data: [], message: 'Logout realizado com sucesso');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            data: new UserResource($request->user()),
            message: 'Usuário autenticado'
        );
    }
}
```

- [ ] **Step 8: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Run TenantRegistrationTest**

```bash
vendor/bin/sail artisan test --compact --filter=TenantRegistrationTest
```

Expected: 6 tests pass.

- [ ] **Step 10: Commit**

```bash
git add app/Domain/Auth/Data/RegisterData.php app/Http/Requests/Api/V1/Dashboard/Auth/RegisterRequest.php app/Domain/Auth/Services/RegisterTenantService.php app/Http/Resources/Api/V1/Dashboard/Tenant/TenantResource.php app/Http/Controllers/Api/V1/Dashboard/AuthController.php tests/Feature/TenantRegistrationTest.php
git commit -m "feat: add tenant registration endpoint (POST /api/v1/register)"
```

---

### Task 5: Update Pest.php helper + update all existing tests

**Files:**
- Modify: `tests/Pest.php`
- Modify: `tests/Feature/Api/V1/Dashboard/AuthTest.php`
- Modify: `tests/Feature/RoleTest.php`
- Modify: `tests/Feature/AvatarTest.php`

**Interfaces:**
- Consumes: `Tenant::factory()` (Task 1), `$tenant->makeCurrent()` (Task 1)
- Produces: global `tenantActingAs(User): User` helper

- [ ] **Step 1: Update `tests/Pest.php`**

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Authenticate as a user AND make their tenant current.
 * Use this in every test that calls a tenant-scoped API route.
 */
function tenantActingAs(User $user): User
{
    $user->tenant->makeCurrent();
    Sanctum::actingAs($user);

    return $user;
}
```

- [ ] **Step 2: Update `AuthTest` — add tenant context to every test**

Replace the entire `tests/Feature/Api/V1/Dashboard/AuthTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

function userPayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'test@example.com',
        'password' => 'password',
    ], $overrides);
}

it('logs in a user and returns token with user data', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', userPayload());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login realizado com sucesso')
        ->assertJsonStructure([
            'data' => [
                'user' => ['uuid', 'name', 'email', 'status', 'avatar_url', 'created_at', 'updated_at'],
                'token',
            ],
        ]);

    expect($response->json('data.token'))->not->toBeEmpty();
    expect($response->json('data.user.email'))->toBe($user->email);
});

it('rejects login with wrong password', function () {
    User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', userPayload(['password' => 'wrong-password']));

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('rejects login with non-existent email', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', userPayload(['email' => 'nobody@example.com']));

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('rejects login for inactive user', function () {
    User::factory()->inactive()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', userPayload());

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('rejects login with missing fields', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', []);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['email', 'password']]);
});

it('returns 422 when X-Tenant-ID header is missing', function () {
    $response = postJson('/api/v1/login', userPayload());

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tenant não encontrado ou inativo');
});

it('returns authenticated user on /me', function () {
    $user = User::factory()->create();

    tenantActingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Usuário autenticado')
        ->assertJsonPath('data.uuid', $user->uuid)
        ->assertJsonPath('data.email', $user->email);
});

it('rejects unauthenticated /me', function () {
    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/me');

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('logs out and revokes token', function () {
    $user = User::factory()->create();

    tenantActingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/logout');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logout realizado com sucesso');

    expect($user->tokens()->count())->toBe(0);
});
```

- [ ] **Step 3: Update `RoleTest` — add tenant context**

Replace the entire `tests/Feature/RoleTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

it('creates admin and user roles', function () {
    expect(Role::where('name', 'admin')->where('guard_name', 'sanctum')->exists())->toBeTrue();
    expect(Role::where('name', 'user')->where('guard_name', 'sanctum')->exists())->toBeTrue();
});

it('can assign admin role to a user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('user'))->toBeFalse();
});

it('blocks non-admin user from role:admin protected routes', function () {
    Route::middleware(['auth:sanctum', 'role:admin'])
        ->get('/test-admin', fn () => response()->json(['ok' => true]));

    $user = User::factory()->create();
    $user->assignRole('user');

    Sanctum::actingAs($user);

    getJson('/test-admin')->assertForbidden();
});

it('allows admin user through role:admin protected routes', function () {
    Route::middleware(['auth:sanctum', 'role:admin'])
        ->get('/test-admin', fn () => response()->json(['ok' => true]));

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    getJson('/test-admin')->assertOk();
});
```

- [ ] **Step 4: Update `AvatarTest` — add tenant context**

Replace the entire `tests/Feature/AvatarTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

it('returns empty string for avatar_url when no avatar is uploaded', function () {
    $user = User::factory()->create();

    tenantActingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.avatar_url', '');
});

it('stores avatar and returns url via /me', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $user->addMedia($file)->toMediaCollection('avatar');

    tenantActingAs($user);

    $response = $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/me');

    $response->assertOk();
    expect($response->json('data.avatar_url'))->not->toBeEmpty();
});

it('replaces previous avatar on new upload because collection is singleFile', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('avatar');

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);
});
```

- [ ] **Step 5: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Run all tests**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass (previous 25 + TenantModelTest 7 + TenantRegistrationTest 6 = 38 total).

- [ ] **Step 7: Commit**

```bash
git add tests/Pest.php tests/Feature/Api/V1/Dashboard/AuthTest.php tests/Feature/RoleTest.php tests/Feature/AvatarTest.php
git commit -m "test: update all existing tests with tenant context"
```

---

### Task 6: TenantTest — isolation and full flow

**Files:**
- Create: `tests/Feature/TenantTest.php`

**Interfaces:**
- Consumes: `EnsureTenant` middleware (Task 3), `BelongsToTenant` scope (Task 3), `tenantActingAs()` (Task 5)

- [ ] **Step 1: Create TenantTest**

```bash
vendor/bin/sail artisan make:test --pest TenantTest --no-interaction
```

Replace `tests/Feature/TenantTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
});

it('returns 422 when X-Tenant-ID header is missing on protected route', function () {
    $response = postJson('/api/v1/login', ['email' => 'a@a.com', 'password' => 'password']);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tenant não encontrado ou inativo');
});

it('returns 422 for unknown tenant UUID in header', function () {
    $response = $this->withHeader('X-Tenant-ID', '00000000-0000-0000-0000-000000000000')
        ->postJson('/api/v1/login', ['email' => 'a@a.com', 'password' => 'password']);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tenant não encontrado ou inativo');
});

it('returns 422 for inactive tenant', function () {
    $tenant = Tenant::factory()->inactive()->create();

    $response = $this->withHeader('X-Tenant-ID', $tenant->uuid)
        ->postJson('/api/v1/login', ['email' => 'a@a.com', 'password' => 'password']);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Tenant não encontrado ou inativo');
});

it('isolates users between tenants — tenant A cannot see tenant B users', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $tenantA->makeCurrent();
    $userA = User::factory()->create(['email' => 'user@tenant-a.com']);

    $tenantB->makeCurrent();
    $userB = User::factory()->create(['email' => 'user@tenant-b.com']);

    // From tenant A's perspective, only userA exists
    $tenantA->makeCurrent();
    expect(User::where('email', 'user@tenant-a.com')->exists())->toBeTrue();
    expect(User::where('email', 'user@tenant-b.com')->exists())->toBeFalse();

    // From tenant B's perspective, only userB exists
    $tenantB->makeCurrent();
    expect(User::where('email', 'user@tenant-b.com')->exists())->toBeTrue();
    expect(User::where('email', 'user@tenant-a.com')->exists())->toBeFalse();
});

it('login only works for users within the correct tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $tenantA->makeCurrent();
    User::factory()->create(['email' => 'user@a.com', 'password' => 'password']);

    // Trying to log in to tenant A's user via tenant B's header — should fail
    $response = $this->withHeader('X-Tenant-ID', $tenantB->uuid)
        ->postJson('/api/v1/login', ['email' => 'user@a.com', 'password' => 'password']);

    $response->assertUnauthorized();
});

it('register endpoint does not require X-Tenant-ID header', function () {
    $response = postJson('/api/v1/register', [
        'tenant_name' => 'New Corp',
        'tenant_slug' => 'new-corp',
        'name' => 'Admin User',
        'email' => 'admin@new.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated();
});
```

- [ ] **Step 2: Run TenantTest**

```bash
vendor/bin/sail artisan test --compact --filter=TenantTest
```

Expected: 6 tests pass.

- [ ] **Step 3: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 4: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/TenantTest.php
git commit -m "test: add TenantTest for isolation, middleware, and registration flow"
```
