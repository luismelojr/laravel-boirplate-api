# Round 1 — Infraestrutura e Qualidade

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add GitHub Actions CI, global API rate limiting, schedule monitoring, and architecture tests to harden the boilerplate.

**Architecture:** Four independent tasks. Tasks 2–4 modify existing PHP files; Task 1 creates a new YAML file. All tasks are additive — nothing is removed.

**Tech Stack:** GitHub Actions, spatie/laravel-schedule-monitor, pestphp/pest-plugin-arch, Laravel RateLimiter.

## Global Constraints

- All commands: `vendor/bin/sail <cmd>` from `/Users/luis/code/projetos/boirplate`
- `vendor/bin/sail bin pint --dirty --format agent` after every PHP change
- Tests: `vendor/bin/sail artisan test --compact`
- All 66 existing tests must keep passing

---

### Task 1: GitHub Actions CI

**Files:**
- Create: `.github/workflows/tests.yml`

**Interfaces:**
- Produces: automated test pipeline on push/PR to `main`

- [ ] **Step 1: Create `.github/workflows/tests.yml`**

```yaml
name: Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    env:
      DB_CONNECTION: mysql
      DB_HOST: 127.0.0.1
      DB_PORT: 3306
      DB_DATABASE: testing
      DB_USERNAME: root
      DB_PASSWORD: ""
      SESSION_DRIVER: array
      QUEUE_CONNECTION: sync
      CACHE_STORE: array
      MAIL_MAILER: log
      AWS_ACCESS_KEY_ID: sail
      AWS_SECRET_ACCESS_KEY: password
      AWS_DEFAULT_REGION: us-east-1
      AWS_BUCKET: backups
      AWS_ENDPOINT: "http://localhost:9000"
      AWS_USE_PATH_STYLE_ENDPOINT: "true"
      BACKUP_NOTIFICATION_EMAIL: ci@example.com
      ADMIN_EMAIL: admin@example.com
      ADMIN_PASSWORD: password
      ADMIN_TENANT_NAME: "CI Tenant"
      ADMIN_TENANT_SLUG: ci-tenant

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.5"
          extensions: mbstring, dom, fileinfo, mysql, redis, zip, gd
          coverage: none

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Copy environment file
        run: cp .env.example .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --no-interaction --force

      - name: Run tests
        run: php artisan test --compact
```

- [ ] **Step 2: Verify workflow file is valid YAML**

```bash
cat .github/workflows/tests.yml
```

Expected: file prints without error.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "ci: add GitHub Actions test pipeline (PHP 8.5 + MySQL 8.4)"
```

---

### Task 2: Global API Rate Limiting + RateLimitTest

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/api.php`
- Create: `tests/Feature/RateLimitTest.php`

**Interfaces:**
- Produces: `throttle:api` limiter (60/min authenticated, 30/min anonymous), `throttle:10,1` on `/register`, 429 responses on limit exceeded

- [ ] **Step 1: Write failing `RateLimitTest`**

```bash
vendor/bin/sail artisan make:test --pest RateLimitTest --no-interaction
```

Replace `tests/Feature/RateLimitTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

it('api rate limiter allows 60 requests per minute for authenticated users', function () {
    $user = User::factory()->create();
    $rateLimiter = RateLimiter::limiter('api');

    $request = \Illuminate\Http\Request::create('/api/v1/me', 'GET');
    $request->setUserResolver(fn () => $user);

    $limit = $rateLimiter($request);

    expect($limit->maxAttempts)->toBe(60)
        ->and($limit->key)->toBe((string) $user->id);
});

it('api rate limiter allows 30 requests per minute for anonymous users', function () {
    $rateLimiter = RateLimiter::limiter('api');

    $request = \Illuminate\Http\Request::create('/api/v1/me', 'GET');

    $limit = $rateLimiter($request);

    expect($limit->maxAttempts)->toBe(30);
});

it('returns 429 after exceeding anonymous rate limit on tenant routes', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
            ->getJson('/api/v1/me');
    }

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson('/api/v1/me')
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});

it('returns 429 after exceeding rate limit on register endpoint', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/register', []);
    }

    $this->postJson('/api/v1/register', [])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=RateLimitTest
```

Expected: fails — `api` limiter not found.

- [ ] **Step 3: Add `api` rate limiter to `AppServiceProvider::boot()`**

Add the new limiter after the existing `auth` limiter:

```php
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(30)->by($request->ip());
});
```

- [ ] **Step 4: Update `routes/api.php`**

Add `throttle:10,1` to the register route and `throttle:api` to the `ensure_tenant` group:

```php
<?php

use App\Http\Controllers\Api\V1\Dashboard\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public — no tenant required
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');

    // All routes below require a valid X-Tenant-ID header
    Route::middleware(['ensure_tenant', 'throttle:api'])->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
        Route::post('invite/accept', [AuthController::class, 'acceptInvite']);

        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('email/resend', [AuthController::class, 'resendVerification']);
        });

        // Admin-only routes
        Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
            Route::post('users/invite', [AdminUserController::class, 'invite']);
        });
    });
});
```

- [ ] **Step 5: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Run RateLimitTest**

```bash
vendor/bin/sail artisan test --compact --filter=RateLimitTest
```

Expected: 4 tests pass.

- [ ] **Step 7: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all 70 tests pass.

- [ ] **Step 8: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/api.php tests/Feature/RateLimitTest.php
git commit -m "feat: add global API rate limiting (60/min auth, 30/min anon, 10/min register)"
```

---

### Task 3: spatie/laravel-schedule-monitor + ScheduleCheck

**Files:**
- Create: `config/schedule-monitor.php` (published)
- Modify: `bootstrap/app.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Produces: `ScheduleCheck` in `/health`, `->monitorAtSchedule()` on all scheduled commands

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require spatie/laravel-schedule-monitor
```

- [ ] **Step 2: Publish migrations and config**

```bash
vendor/bin/sail artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="schedule-monitor-migrations" --no-interaction
vendor/bin/sail artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="schedule-monitor-config" --no-interaction
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 3: Add `->monitorAtSchedule()` to all commands in `bootstrap/app.php`**

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('horizon:snapshot')->everyFiveMinutes()->monitorAtSchedule();
    $schedule->command('backup:run')->twiceDaily(6, 23)->monitorAtSchedule();
    $schedule->command('backup:clean')->dailyAt('00:30')->monitorAtSchedule();
})
```

- [ ] **Step 4: Add `ScheduleCheck` to `AppServiceProvider`**

Add the import and the check. The `api` rate limiter was already added in Task 2 — only add `ScheduleCheck`:

```php
use Spatie\Health\Checks\Checks\ScheduleCheck;
```

Update `Health::checks([...])` to include `ScheduleCheck::new()` as the last item:

```php
Health::checks([
    DatabaseCheck::new(),
    RedisCheck::new(),
    HorizonCheck::new(),
    QueueCheck::new(),
    BackupsCheck::new()->name('Backup')->onDisk('s3')->youngestBackShouldHaveBeenMadeBefore(now()->subDays(2)),
    ScheduleCheck::new(),
]);
```

- [ ] **Step 5: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass. Note: `ScheduleCheck` will show `warning` in the health response (no tasks have run yet) — this is expected and doesn't affect HTTP 200.

- [ ] **Step 7: Commit**

```bash
git add bootstrap/app.php app/Providers/AppServiceProvider.php config/schedule-monitor.php database/migrations/ composer.json composer.lock
git commit -m "feat: add spatie/laravel-schedule-monitor with ScheduleCheck in health endpoint"
```

---

### Task 4: pestphp/pest-plugin-arch — Architecture Tests

**Files:**
- Create: `tests/ArchTest.php`

**Interfaces:**
- Produces: 4 architectural rules enforced on every test run

- [ ] **Step 1: Install the package**

```bash
vendor/bin/sail composer require pestphp/pest-plugin-arch --dev
```

- [ ] **Step 2: Create `tests/ArchTest.php`**

```php
<?php

arch('Auth DTOs extend Spatie LaravelData')
    ->expect('App\Domain\Auth\Data')
    ->toExtend(\Spatie\LaravelData\Data::class);

arch('Domain services have a handle method')
    ->expect('App\Domain')
    ->classes()
    ->toHaveSuffix('Service')
    ->toHaveMethod('handle');

arch('Controllers do not use response() helper directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('response');

arch('App\Models classes are proper classes')
    ->expect('App\Models')
    ->toBeClasses();
```

- [ ] **Step 3: Run ArchTest to verify all rules pass**

```bash
vendor/bin/sail artisan test --compact --filter=ArchTest
```

Expected: 4 arch tests pass.

- [ ] **Step 4: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass (previous 70 + 4 arch = 74 total).

- [ ] **Step 5: Commit**

```bash
git add tests/ArchTest.php composer.json composer.lock
git commit -m "feat: add pest-plugin-arch with 4 architecture enforcement rules"
```
