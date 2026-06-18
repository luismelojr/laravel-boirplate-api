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
        $schedule->command('schedule-monitor:sync')->hourly();
        $schedule->command('health:schedule-check-heartbeat')->everyMinute();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('backup:run')->twiceDaily(6, 23);
        $schedule->command('backup:clean')->dailyAt('00:30');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionRegister::register($exceptions);
    })->create();
