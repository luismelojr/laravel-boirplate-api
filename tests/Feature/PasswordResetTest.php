<?php

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Auth\ForgotPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    Notification::fake();
});

it('sends reset notification when email exists in tenant', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/forgot-password', ['email' => 'user@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, ForgotPasswordNotification::class);
});

it('returns 200 even when email does not exist (no user enumeration)', function () {
    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/forgot-password', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertNothingSent();
});

it('resets password with valid token', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $token = Str::random(60);
    DB::table('password_reset_tokens')->insert([
        'email' => 'user@example.com',
        'tenant_id' => $this->tenant->id,
        'token' => $token,
        'created_at' => now(),
    ]);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Senha redefinida com sucesso.');

    expect(DB::table('password_reset_tokens')->where('email', 'user@example.com')->exists())->toBeFalse();
});

it('rejects reset with expired token', function () {
    User::factory()->create(['email' => 'user@example.com']);

    DB::table('password_reset_tokens')->insert([
        'email' => 'user@example.com',
        'tenant_id' => $this->tenant->id,
        'token' => 'expiredtoken',
        'created_at' => now()->subHours(2),
    ]);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/reset-password', [
            'token' => 'expiredtoken',
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('rejects reset with invalid token', function () {
    User::factory()->create(['email' => 'user@example.com']);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/reset-password', [
            'token' => 'invalidtoken',
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('allows login with new password after reset', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $token = Str::random(60);
    DB::table('password_reset_tokens')->insert([
        'email' => 'user@example.com',
        'tenant_id' => $this->tenant->id,
        'token' => $token,
        'created_at' => now(),
    ]);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertOk();

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', ['email' => 'user@example.com', 'password' => 'newpassword123'])
        ->assertOk()
        ->assertJsonPath('success', true);
});
