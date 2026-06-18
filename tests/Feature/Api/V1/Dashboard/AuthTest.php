<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
