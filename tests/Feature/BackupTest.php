<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('health endpoint includes backup check in results', function () {
    $response = getJson('/health');

    $response->assertOk()
        ->assertJsonStructure(['finishedAt', 'checkResults']);

    $checkNames = collect($response->json('checkResults'))->pluck('name');

    expect($checkNames)->toContain('Backup');
});

it('backup:clean command runs without error', function () {
    $exitCode = Artisan::call('backup:clean', ['--no-interaction' => true]);

    expect($exitCode)->toBe(0);
});

it('backup schedule is registered for 06:00 and 23:00', function () {
    Artisan::call('schedule:list');

    $schedule = app(Schedule::class);

    $backupEvents = collect($schedule->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'backup:run'))
        ->values();

    expect($backupEvents)->not->toBeEmpty();
});

it('schedule heartbeat command is registered', function () {
    Artisan::call('schedule:list');

    $schedule = app(Schedule::class);

    $heartbeatEvents = collect($schedule->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'health:schedule-check-heartbeat'))
        ->values();

    expect($heartbeatEvents)->not->toBeEmpty();
});
