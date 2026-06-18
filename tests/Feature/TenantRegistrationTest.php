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
