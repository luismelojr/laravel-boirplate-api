<?php

use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\Auth\InviteUserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

    Notification::fake();
});

it('admin can invite a user and notification is sent', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    tenantActingAs($admin);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/admin/users/invite', [
            'email' => 'invited@example.com',
            'role' => 'user',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Convite enviado com sucesso.')
        ->assertJsonStructure(['data' => ['user' => ['uuid', 'email', 'status']]]);

    $invitedUser = User::withoutGlobalScopes()->where('email', 'invited@example.com')->first();
    expect($invitedUser->status)->toBe(UserStatusEnum::Pending);

    Notification::assertSentTo($invitedUser, InviteUserNotification::class);
});

it('non-admin cannot invite users', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    tenantActingAs($user);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/admin/users/invite', [
            'email' => 'somebody@example.com',
            'role' => 'user',
        ])
        ->assertForbidden();
});

it('accepts invite and activates account', function () {
    $invitation = UserInvitation::factory()
        ->for($this->tenant)
        ->for(User::factory()->pending()->for($this->tenant))
        ->create();

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/invite/accept', [
            'token' => $invitation->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Conta ativada com sucesso.')
        ->assertJsonStructure(['data' => ['user', 'token']]);

    $user = $invitation->user->fresh();
    expect($user->status)->toBe(UserStatusEnum::Active);
    expect($user->hasVerifiedEmail())->toBeTrue();
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

it('rejects expired invite token', function () {
    $invitation = UserInvitation::factory()
        ->expired()
        ->for($this->tenant)
        ->for(User::factory()->pending()->for($this->tenant))
        ->create();

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/invite/accept', [
            'token' => $invitation->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('rejects already accepted invite', function () {
    $invitation = UserInvitation::factory()
        ->accepted()
        ->for($this->tenant)
        ->for(User::factory()->for($this->tenant))
        ->create();

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/invite/accept', [
            'token' => $invitation->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('invited user receives correct role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    tenantActingAs($admin);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/admin/users/invite', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ])
        ->assertCreated();

    $invitedUser = User::withoutGlobalScopes()->where('email', 'newadmin@example.com')->first();
    expect($invitedUser->hasRole('admin'))->toBeTrue();
});
