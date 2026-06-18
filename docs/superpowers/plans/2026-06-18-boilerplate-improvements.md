# Boilerplate Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 6 targeted improvements to the Laravel 13 API boilerplate: fix anti-patterns, spatie/laravel-data DTOs, RBAC, media uploads, health endpoint, and local email.

**Architecture:** Each improvement is independent and self-contained. Tasks 1–2 are prerequisites for running tests cleanly; Tasks 3–6 build on a stable base. All changes follow the existing Domain/Http/Support structure and use `vendor/bin/sail` for all commands.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, spatie/laravel-data, spatie/laravel-permission, spatie/laravel-media-library, spatie/laravel-health, Mailpit, MySQL, Redis, Sanctum.

## Global Constraints

- All commands run via `vendor/bin/sail` from `/Users/luis/code/projetos/boirplate`
- `declare(strict_types=1)` at the top of every new PHP file
- Validation messages in Portuguese
- `vendor/bin/sail bin pint --dirty --format agent` after every PHP change
- Tests run with `vendor/bin/sail artisan test --compact`
- Route model binding uses `uuid`; `id` is hidden from all API responses

---

### Task 1: Fix LoginUserService anti-patterns

**Files:**
- Modify: `app/Domain/Auth/Services/LoginUserService.php`

**Interfaces:**
- Produces: `LoginUserService::handle(LoginUserData $data): array` — same signature, cleaner body

- [ ] **Step 1: Replace LoginUserService**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\LoginUserData;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    public function handle(LoginUserData $data): array
    {
        $user = User::query()
            ->where('email', $data->email)
            ->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('Credenciais inválidas');
        }

        if ($user->status === UserStatusEnum::Inactive) {
            throw new AuthenticationException('Usuário inativo. Contate o administrador do sistema.');
        }

        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
```

- [ ] **Step 2: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 3: Run existing auth tests**

```bash
vendor/bin/sail artisan test --compact --filter=AuthTest
```

Expected: all 8 tests pass.

- [ ] **Step 4: Commit**

```bash
git add app/Domain/Auth/Services/LoginUserService.php
git commit -m "refactor: remove redundant try/catch and use Sanctum token relation in LoginUserService"
```

---

### Task 2: Install spatie/laravel-data and update LoginUserData

**Files:**
- Modify: `app/Domain/Auth/Data/LoginUserData.php`

**Interfaces:**
- Consumes: `Spatie\LaravelData\Data`
- Produces: `LoginUserData::from(array): static` — inherited from parent, no manual `from()` needed

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-data
```

- [ ] **Step 2: Replace LoginUserData**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Data;

class LoginUserData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
```

- [ ] **Step 3: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 4: Run auth tests — `Data::from()` is inherited, controller call is unchanged**

```bash
vendor/bin/sail artisan test --compact --filter=AuthTest
```

Expected: all 8 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Domain/Auth/Data/LoginUserData.php composer.json composer.lock
git commit -m "feat: replace manual DTOs with spatie/laravel-data base class"
```

---

### Task 3: Install spatie/laravel-permission — RBAC with admin + user roles

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `app/Models/User.php`
- Modify: `config/permission.php` (published by artisan)
- Create: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/seeders/UserSeeder.php`
- Modify: `routes/api.php`
- Modify: `app/Providers/HorizonServiceProvider.php`
- Modify: `app/Providers/TelescopeServiceProvider.php`
- Create: `tests/Feature/Api/V1/Dashboard/RoleTest.php`

**Interfaces:**
- Produces: `$user->hasRole('admin')`, `$user->hasRole('user')`, middleware alias `role:admin`

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-permission
```

- [ ] **Step 2: Publish config and migrations**

```bash
vendor/bin/sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
```

- [ ] **Step 3: Run the permissions migrations**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 4: Configure default guard to sanctum in `config/permission.php`**

Find the `defaults` array and change `guard` from `web` to `sanctum`:

```php
'defaults' => [
    'guard' => 'sanctum',
    'guard_to_override' => null,
],
```

- [ ] **Step 5: Register permission middleware aliases in `bootstrap/app.php`**

```php
<?php

use App\Support\Api\ApiExceptionRegister;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionRegister::register($exceptions);
    })->create();
```

- [ ] **Step 6: Add HasRoles trait to User model**

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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'status',
        'avatar_url',
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
}
```

- [ ] **Step 7: Create RoleSeeder**

```bash
vendor/bin/sail artisan make:class database/seeders/RoleSeeder --no-interaction
```

Replace the generated file:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
    }
}
```

- [ ] **Step 8: Update DatabaseSeeder to call RoleSeeder first**

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
            UserSeeder::class,
        ]);
    }
}
```

- [ ] **Step 9: Update UserSeeder to assign admin role**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'junimhs10@gmail.com'],
            [
                'name' => 'Luis Henrique',
                'password' => '3010Rpwt28@',
                'status' => 'active',
            ]
        );

        $user->assignRole('admin');
    }
}
```

- [ ] **Step 10: Update api.php with admin-protected route group example**

```php
<?php

use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
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
```

- [ ] **Step 11: Update HorizonServiceProvider gate to use hasRole**

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return app()->environment('local')
                || $user?->hasRole('admin');
        });
    }
}
```

- [ ] **Step 12: Update TelescopeServiceProvider gate to use hasRole**

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            return $user->hasRole('admin');
        });
    }
}
```

- [ ] **Step 13: Write RoleTest**

```bash
vendor/bin/sail artisan make:test --pest RoleTest --no-interaction
```

Replace `tests/Feature/RoleTest.php`:

```php
<?php

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

- [ ] **Step 14: Run RoleTest**

```bash
vendor/bin/sail artisan test --compact --filter=RoleTest
```

Expected: 4 tests pass.

- [ ] **Step 15: Run AuthTest to check for regressions**

```bash
vendor/bin/sail artisan test --compact --filter=AuthTest
```

Expected: all 8 tests pass.

- [ ] **Step 16: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 17: Commit**

```bash
git add bootstrap/app.php app/Models/User.php config/permission.php database/seeders/ routes/api.php app/Providers/HorizonServiceProvider.php app/Providers/TelescopeServiceProvider.php tests/Feature/RoleTest.php composer.json composer.lock
git commit -m "feat: add spatie/laravel-permission with admin and user roles"
```

---

### Task 4: Add Mailpit to compose.yaml for local email

**Files:**
- Modify: `compose.yaml`
- Modify: `.env`
- Modify: `.env.example`

**Interfaces:**
- Produces: SMTP on `mailpit:1025` (inside Sail network), dashboard at `http://localhost:8025`

- [ ] **Step 1: Add mailpit service to compose.yaml — insert before the `networks:` key**

```yaml
    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            - '${FORWARD_MAILPIT_PORT:-1025}:1025'
            - '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025'
        networks:
            - sail
```

- [ ] **Step 2: Update .env mail section**

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@boirplate.test"
MAIL_FROM_NAME="${APP_NAME}"
```

- [ ] **Step 3: Update .env.example with the same mail config**

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@boirplate.test"
MAIL_FROM_NAME="${APP_NAME}"
```

- [ ] **Step 4: Restart Sail to pick up the new service**

```bash
vendor/bin/sail down && vendor/bin/sail up -d
```

- [ ] **Step 5: Verify Mailpit is running**

```bash
vendor/bin/sail ps
```

Expected: `boirplate-mailpit-1` listed as `running`.

- [ ] **Step 6: Commit**

```bash
git add compose.yaml .env.example
git commit -m "feat: add Mailpit service to Sail for local email testing"
```

---

### Task 5: Install spatie/laravel-media-library — replace avatar_url

**Files:**
- Create: `database/migrations/*_drop_avatar_url_from_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Resources/Api/V1/Dashboard/User/UserResource.php`
- Modify: `database/factories/UserFactory.php`
- Create: `tests/Feature/Api/V1/Dashboard/AvatarTest.php`

**Interfaces:**
- Produces: `$user->getFirstMediaUrl('avatar')` returns string (empty when no media), `$user->addMedia($file)->toMediaCollection('avatar')`, `$user->getMedia('avatar')` returns collection

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-medialibrary
```

- [ ] **Step 2: Publish media library migrations and run them**

```bash
vendor/bin/sail artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations" --no-interaction
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 3: Create migration to drop avatar_url**

```bash
vendor/bin/sail artisan make:migration drop_avatar_url_from_users_table --no-interaction
```

Replace the generated migration body (keep the class wrapper, replace `up` and `down`):

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
            $table->dropColumn('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('status');
        });
    }
};
```

```bash
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 4: Update User model — add HasMedia, InteractsWithMedia, registerMediaCollections, registerMediaConversions**

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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

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

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();
    }
}
```

- [ ] **Step 5: Update UserResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status->value,
            'avatar_url' => $this->getFirstMediaUrl('avatar'),
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i:s'),
        ];
    }
}
```

- [ ] **Step 6: Update UserFactory — remove avatar_url**

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
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

- [ ] **Step 7: Run AuthTest — assertJsonStructure still passes because UserResource still returns `avatar_url` key (now sourced from getFirstMediaUrl)**

```bash
vendor/bin/sail artisan test --compact --filter=AuthTest
```

Expected: all 8 tests pass.

- [ ] **Step 8: Write AvatarTest**

```bash
vendor/bin/sail artisan make:test --pest AvatarTest --no-interaction
```

Replace `tests/Feature/AvatarTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns empty string for avatar_url when no avatar is uploaded', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.avatar_url', '');
});

it('stores avatar and returns url via /me', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $user->addMedia($file)->toMediaCollection('avatar');

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/me');

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

- [ ] **Step 9: Run AvatarTest**

```bash
vendor/bin/sail artisan test --compact --filter=AvatarTest
```

Expected: 3 tests pass.

- [ ] **Step 10: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 11: Commit**

```bash
git add app/Models/User.php app/Http/Resources/Api/V1/Dashboard/User/UserResource.php database/factories/UserFactory.php database/migrations/ tests/Feature/AvatarTest.php composer.json composer.lock
git commit -m "feat: replace avatar_url string column with spatie/laravel-medialibrary collection"
```

---

### Task 6: Install spatie/laravel-health — /health endpoint

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/HealthTest.php`

**Interfaces:**
- Produces: `GET /health` → JSON `{ finishedAt, checkResults: [{ name, status, ... }] }` with checks: Database, Redis, Horizon, Queue

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-health
```

- [ ] **Step 2: Publish config and migrations**

```bash
vendor/bin/sail artisan vendor:publish --tag="health-config" --no-interaction
vendor/bin/sail artisan vendor:publish --tag="health-migrations" --no-interaction
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 3: Register health checks in AppServiceProvider**

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            HorizonCheck::new(),
            QueueCheck::new(),
        ]);
    }
}
```

- [ ] **Step 4: Add /health route to routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthCheckJsonResultsController::class);
```

- [ ] **Step 5: Write HealthTest**

```bash
vendor/bin/sail artisan make:test --pest HealthTest --no-interaction
```

Replace `tests/Feature/HealthTest.php`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Facades\Health;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns 200 from /health endpoint', function () {
    getJson('/health')->assertOk();
});

it('health response contains finishedAt and checkResults', function () {
    $response = getJson('/health');

    $response->assertOk()
        ->assertJsonStructure(['finishedAt', 'checkResults']);
});

it('database check reports ok status', function () {
    Health::checks([DatabaseCheck::new()]);

    $response = getJson('/health');

    $response->assertOk();

    $db = collect($response->json('checkResults'))->firstWhere('name', 'Database');

    expect($db)->not->toBeNull()
        ->and($db['status'])->toBe('ok');
});

it('redis check reports ok status', function () {
    Health::checks([RedisCheck::new()]);

    $response = getJson('/health');

    $response->assertOk();

    $redis = collect($response->json('checkResults'))->firstWhere('name', 'Redis');

    expect($redis)->not->toBeNull()
        ->and($redis['status'])->toBe('ok');
});
```

- [ ] **Step 6: Run HealthTest**

```bash
vendor/bin/sail artisan test --compact --filter=HealthTest
```

Expected: 4 tests pass. HorizonCheck and QueueCheck may show `warning` when workers are not running — this is expected and does not affect the 200 response.

- [ ] **Step 7: Run full test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 8: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/web.php tests/Feature/HealthTest.php composer.json composer.lock
git commit -m "feat: add spatie/laravel-health with DB, Redis, Horizon, and Queue checks at /health"
```
