# Round 1 — Infraestrutura e Qualidade — Design Spec

**Date:** 2026-06-18
**Status:** Approved

---

## Overview

Quatro melhorias independentes de infraestrutura e qualidade de código:

1. **GitHub Actions CI** — pipeline automático de testes
2. **Rate limiting global** — proteção da API contra abuso
3. **spatie/laravel-schedule-monitor** — monitoramento de comandos agendados
4. **pestphp/pest-plugin-arch** — testes de arquitetura

---

## 1. GitHub Actions CI

### Arquivo

`.github/workflows/tests.yml`

### Pipeline

Disparo: `push` e `pull_request` para a branch `main`.

Steps:
1. `actions/checkout@v4`
2. `shivammathur/setup-php@v2` — PHP 8.5, extensões: mbstring, dom, fileinfo, mysql, redis
3. Serviço `mysql:8.4` com `MYSQL_DATABASE=testing`, `MYSQL_ROOT_PASSWORD=password`
4. `composer install --no-interaction --prefer-dist --optimize-autoloader`
5. `cp .env.example .env`
6. Configurar `.env` para CI: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=testing`, `DB_USERNAME=root`, `DB_PASSWORD=password`
7. `php artisan key:generate`
8. `php artisan migrate --no-interaction --force`
9. `php artisan test --compact`

Sem Redis no CI (testes usam `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array` via `.env` de CI).

### .env de CI

O passo 6 configura via `echo "VAR=VALUE" >> .env` ou `sed`. Valores:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=testing
DB_USERNAME=root
DB_PASSWORD=password
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
CACHE_STORE=array
MAIL_MAILER=log
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=backups
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## 2. Rate Limiting Global

### `AppServiceProvider::boot()`

Novo limiter `api`:

```php
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(30)->by($request->ip());
});
```

### `routes/api.php`

- Rota pública `POST /register` ganha `throttle:10,1` (10 req/min por IP — previne criação massiva de tenants).
- Grupo `ensure_tenant` ganha `throttle:api` (aplicado a todas as rotas autenticadas e tenant-scoped).

```php
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');

Route::middleware(['ensure_tenant', 'throttle:api'])->group(function () {
    // ... todas as rotas existentes
});
```

### Resposta 429

Já tratada pelo `ApiExceptionRegister` (`TooManyRequestsHttpException` → 429 JSON).

---

## 3. spatie/laravel-schedule-monitor

### Instalação

```bash
composer require spatie/laravel-schedule-monitor
php artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="schedule-monitor-migrations"
php artisan migrate
```

### `bootstrap/app.php` — monitorar comandos existentes

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('horizon:snapshot')->everyFiveMinutes()->monitorAtSchedule();
    $schedule->command('backup:run')->twiceDaily(6, 23)->monitorAtSchedule();
    $schedule->command('backup:clean')->dailyAt('00:30')->monitorAtSchedule();
})
```

### `AppServiceProvider` — adicionar `ScheduleCheck` ao health

```php
use Spatie\Health\Checks\Checks\ScheduleCheck;

Health::checks([
    DatabaseCheck::new(),
    RedisCheck::new(),
    HorizonCheck::new(),
    QueueCheck::new(),
    BackupsCheck::new(),
    ScheduleCheck::new(),
]);
```

### `config/schedule-monitor.php`

Publicado pelo pacote. Manter defaults — o pacote armazena histórico em `monitored_scheduled_task_log_items`.

---

## 4. pestphp/pest-plugin-arch

### Instalação

```bash
composer require pestphp/pest-plugin-arch --dev
```

### `tests/ArchTest.php`

```php
<?php

arch('DTOs in Data/ extend Spatie LaravelData')
    ->expect('App\Domain')
    ->classes()
    ->toExtend(\Spatie\LaravelData\Data::class)
    ->ignoring('App\Domain\Tenant');

arch('Services are only in App\Domain')
    ->expect('App')
    ->classes()
    ->toHaveSuffix('Service')
    ->toBeIn('App\Domain');

arch('Controllers do not use response() helper')
    ->expect('App\Http\Controllers')
    ->not->toUse('response');

arch('Models extend Illuminate Model and live in App\Models')
    ->expect('App\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class)
    ->ignoring(\App\Models\Tenant::class);
```

**Nota:** `Tenant` é excluído de algumas regras porque estende `Spatie\Multitenancy\Models\Tenant` (não `Data` nem `Model` diretamente).

---

## 5. Testes

- **CI**: sem testes adicionais — o próprio pipeline é o artefato testável.
- **Rate limiting**: `RateLimitTest` — chama `/api/v1/login` 31 vezes como IP anônimo → 31ª retorna 429; chama 61 vezes como usuário autenticado → 61ª retorna 429.
- **Schedule monitor**: sem testes diretos — `HealthTest` existente já cobre `/health`; o `ScheduleCheck` aparece nos resultados.
- **Arch tests**: `ArchTest.php` roda junto com a suite normal.

---

## 6. Ordem de Implementação

| # | Tarefa |
|---|--------|
| 1 | GitHub Actions CI (`.github/workflows/tests.yml`) |
| 2 | Rate limiting global (`AppServiceProvider` + `routes/api.php`) + `RateLimitTest` |
| 3 | spatie/laravel-schedule-monitor (install + config + `ScheduleCheck` no health) |
| 4 | pestphp/pest-plugin-arch (install + `tests/ArchTest.php`) |
