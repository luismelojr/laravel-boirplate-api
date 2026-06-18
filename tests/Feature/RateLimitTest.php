<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    $request = Request::create('/api/v1/me', 'GET');
    $request->setUserResolver(fn () => $user);

    $limit = $rateLimiter($request);

    expect($limit->maxAttempts)->toBe(60)
        ->and($limit->key)->toBe((string) $user->id);
});

it('api rate limiter allows 30 requests per minute for anonymous users', function () {
    $rateLimiter = RateLimiter::limiter('api');

    $request = Request::create('/api/v1/me', 'GET');

    $limit = $rateLimiter($request);

    expect($limit->maxAttempts)->toBe(30);
});

it('returns 429 after exceeding anonymous rate limit on tenant routes', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
            ->postJson('/api/v1/invite/accept', []);
    }

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/invite/accept', [])
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
