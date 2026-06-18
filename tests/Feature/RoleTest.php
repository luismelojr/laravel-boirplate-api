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
