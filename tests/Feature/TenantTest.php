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
