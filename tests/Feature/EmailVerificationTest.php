<?php

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    Notification::fake();
});

it('blocks login for unverified user', function () {
    User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
        'password' => 'password',
    ]);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', ['email' => 'unverified@example.com', 'password' => 'password'])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'E-mail não verificado. Verifique sua caixa de entrada.');
});

it('allows login for verified user', function () {
    User::factory()->create([
        'email' => 'verified@example.com',
        'password' => 'password',
    ]);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/login', ['email' => 'verified@example.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('resends verification notification', function () {
    $user = User::factory()->unverified()->create();

    Sanctum::actingAs($user);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->postJson('/api/v1/email/resend')
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('verifies email with valid signed URL', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson($path)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects verify with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => 'wronghash']
    );

    $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

    $this->withHeader('X-Tenant-ID', $this->tenant->uuid)
        ->getJson($path)
        ->assertForbidden();
});

it('sends verification notification after tenant registration', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

    postJson('/api/v1/register', [
        'tenant_name' => 'New Corp',
        'tenant_slug' => 'new-corp',
        'name' => 'Admin',
        'email' => 'admin@new.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::withoutGlobalScopes()->where('email', 'admin@new.com')->first();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});
