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

it('health response contains schedule check result', function () {
    $response = getJson('/health');

    $response->assertOk();

    $checkNames = collect($response->json('checkResults'))->pluck('name');

    expect($checkNames)->toContain('Schedule');
});

it('database check reports ok status', function () {
    Health::clearChecks()->checks([DatabaseCheck::new()]);

    $response = getJson('/health');

    $response->assertOk();

    $db = collect($response->json('checkResults'))->firstWhere('name', 'Database');

    expect($db)->not->toBeNull()
        ->and($db['status'])->toBe('ok');
});

it('redis check reports ok status', function () {
    Health::clearChecks()->checks([RedisCheck::new()]);

    $response = getJson('/health');

    $response->assertOk();

    $redis = collect($response->json('checkResults'))->firstWhere('name', 'Redis');

    expect($redis)->not->toBeNull()
        ->and($redis['status'])->toBe('ok');
});
