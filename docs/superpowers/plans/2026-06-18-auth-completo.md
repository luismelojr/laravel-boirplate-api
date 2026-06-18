# Auth Completo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add forgot/reset password, email verification (blocking login), and admin invite flow to the Laravel 13 SaaS multi-tenant API boilerplate.

**Architecture:** All three flows are tenant-scoped via `X-Tenant-ID` header. Password reset tokens extend the `password_reset_tokens` table with a `tenant_id` FK. Email verification uses Laravel's `MustVerifyEmail` with a custom notification and API-compatible signed URL. Invites use a dedicated `user_invitations` table; accepting an invite simultaneously sets the password and verifies the email.

**Tech Stack:** Laravel 13, PHP 8.5, Pest 4, Sanctum, spatie/laravel-multitenancy (X-Tenant-ID), spatie/laravel-permission, resend/resend-laravel, Mailpit (local), Sail/MySQL.

## Global Constraints

- All commands: `vendor/bin/sail <cmd>` from `/Users/luis/code/projetos/boirplate`
- `declare(strict_types=1)` at the top of every new app/ PHP file
- `vendor/bin/sail bin pint --dirty --format agent` after every PHP change
- Tests: `vendor/bin/sail artisan test --compact`
- Validation messages in Portuguese
- All DTOs extend `Spatie\LaravelData\Data`; sensitive fields use `#[Hidden]`
- Response envelope: `{ success, message, data, meta? }` — always use `ApiResponse` trait methods
- Every new service method: `DB::transaction(fn() => ..., 3)` for writes
- Route model binding uses `uuid`; `id` and `tenant_id` hidden from all API responses
- Permission guard: `sanctum`
- Current tenant always set via `EnsureTenant` middleware before any tenant-scoped route

---

### Task 1: Database Foundation — migrations + UserInvitation model + UserStatusEnum update

**Files:**
- Modify: `app/Enums/UserStatusEnum.php`
- Create: `database/migrations/*_add_email_verified_at_to_users_table.php`
- Create: `database/migrations/*_modify_password_reset_tokens_add_tenant_id.php`
- Create: `database/migrations/*_change_users_email_unique_to_tenant_scoped.php`
- Create: `database/migrations/*_create_user_invitations_table.php`
- Create: `app/Models/UserInvitation.php`
- Create: `database/factories/UserInvitationFactory.php`
- Modify: `database/factories/UserFactory.php`

**Interfaces:**
- Produces: `UserStatusEnum::Pending`, `UserInvitation::factory()`, `UserInvitation` model with `user()` and `tenant()` relationships, `UserFactory::unverified()` state, `UserFactory::pending()` state

- [ ] **Step 1: Add `Pending` to `UserStatusEnum`**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 2: Create migration to add `email_verified_at` to users**

```bash
vendor/bin/sail artisan make:migration add_email_verified_at_to_users_table --no-interaction
```

Replace the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
```

- [ ] **Step 3: Create migration to modify `password_reset_tokens` — add `tenant_id` and change PK**

```bash
vendor/bin/sail artisan make:migration modify_password_reset_tokens_add_tenant_id --no-interaction
```

Replace the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary(['email']);
            $table->foreignId('tenant_id')->after('email')->constrained()->cascadeOnDelete();
            $table->primary(['email', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropPrimary(['email', 'tenant_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
            $table->primary('email');
        });
    }
};
```

- [ ] **Step 4: Create migration to make email unique per tenant on users**

```bash
vendor/bin/sail artisan make:migration change_users_email_unique_to_tenant_scoped --no-interaction
```

Replace the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['email', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email', 'tenant_id']);
            $table->unique('email');
        });
    }
};
```

- [ ] **Step 5: Create `user_invitations` migration**

```bash
vendor/bin/sail artisan make:migration create_user_invitations_table --no-interaction
```

Replace the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
```

- [ ] **Step 6: Run all migrations**

```bash
vendor/bin/sail artisan migrate --no-interaction
```

- [ ] **Step 7: Create `UserInvitation` model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    /** @use HasFactory<UserInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

Save to `app/Models/UserInvitation.php`.

- [ ] **Step 8: Create `UserInvitationFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserInvitation>
 */
class UserInvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->pending(),
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addHours(24),
            'accepted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now()->subMinutes(5),
        ]);
    }
}
```

Save to `database/factories/UserInvitationFactory.php`.

- [ ] **Step 9: Update `UserFactory` — add `email_verified_at` default + states**

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => Carbon::now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'email_verified_at' => null,
            'password' => null,
        ]);
    }
}
```

- [ ] **Step 10: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 11: Run existing tests — they should still pass**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all 45 tests pass.

- [ ] **Step 12: Commit**

```bash
git add app/Enums/UserStatusEnum.php database/migrations/ app/Models/UserInvitation.php database/factories/
git commit -m "feat: add auth foundation — email_verified_at, tenant-scoped password_reset_tokens, user_invitations table"
```

---

### Task 2: Forgot Password + Reset Password

**Files:**
- Create: `app/Domain/Auth/Data/ForgotPasswordData.php`
- Create: `app/Domain/Auth/Data/ResetPasswordData.php`
- Create: `app/Http/Requests/Api/V1/Dashboard/Auth/ForgotPasswordRequest.php`
- Create: `app/Http/Requests/Api/V1/Dashboard/Auth/ResetPasswordRequest.php`
- Create: `app/Domain/Auth/Services/ForgotPasswordService.php`
- Create: `app/Domain/Auth/Services/ResetPasswordService.php`
- Create: `app/Notifications/Auth/ForgotPasswordNotification.php`
- Modify: `app/Http/Controllers/Api/V1/Dashboard/AuthController.php`
- Modify: `routes/api.php`
- Create: `tests/Feature/PasswordResetTest.php`

**Interfaces:**
- Consumes: `User` (BelongsToTenant — queries are tenant-scoped), `password_reset_tokens` table with `tenant_id`, `Tenant::current()`
- Produces: `POST /api/v1/forgot-password` → 200, `POST /api/v1/reset-password` → 200

- [ ] **Step 1: Write failing `PasswordResetTest`**

```bash
vendor/bin/sail artisan make:test --pest PasswordResetTest --no-interaction
```

Replace `tests/Feature/PasswordResetTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

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

    Notification::assertSentTo($user, \App\Notifications\Auth\ForgotPasswordNotification::class);
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

    $token = \Illuminate\Support\Str::random(60);
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

    $token = \Illuminate\Support\Str::random(60);
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
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=PasswordResetTest
```

Expected: fails — routes not found.

- [ ] **Step 3: Create `ForgotPasswordData`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Data;

class ForgotPasswordData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
```

Save to `app/Domain/Auth/Data/ForgotPasswordData.php`.

- [ ] **Step 4: Create `ResetPasswordData`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class ResetPasswordData extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
    ) {}
}
```

Save to `app/Domain/Auth/Data/ResetPasswordData.php`.

- [ ] **Step 5: Create `ForgotPasswordRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
```

Save to `app/Http/Requests/Api/V1/Dashboard/Auth/ForgotPasswordRequest.php`.

- [ ] **Step 6: Create `ResetPasswordRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'O token é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
        ];
    }
}
```

Save to `app/Http/Requests/Api/V1/Dashboard/Auth/ResetPasswordRequest.php`.

- [ ] **Step 7: Create `ForgotPasswordNotification`**

```php
<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url').'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Redefinição de senha')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Você solicitou a redefinição de senha da sua conta.')
            ->action('Redefinir senha', $url)
            ->line('Este link expira em 1 hora.')
            ->line('Se você não solicitou a redefinição, ignore este e-mail.');
    }
}
```

Save to `app/Notifications/Auth/ForgotPasswordNotification.php`.

- [ ] **Step 8: Create `ForgotPasswordService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\ForgotPasswordData;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Auth\ForgotPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgotPasswordService
{
    public function handle(ForgotPasswordData $data): void
    {
        $user = User::query()->where('email', $data->email)->first();

        if (! $user) {
            return;
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')
            ->upsert(
                [
                    'email' => $data->email,
                    'tenant_id' => Tenant::current()->getKey(),
                    'token' => $token,
                    'created_at' => now(),
                ],
                ['email', 'tenant_id'],
                ['token', 'created_at']
            );

        $user->notify(new ForgotPasswordNotification($token));
    }
}
```

Save to `app/Domain/Auth/Services/ForgotPasswordService.php`.

- [ ] **Step 9: Create `ResetPasswordService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\ResetPasswordData;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetPasswordService
{
    public function handle(ResetPasswordData $data): void
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $data->email)
            ->where('tenant_id', Tenant::current()->getKey())
            ->first();

        if (! $record || now()->subHour()->isAfter($record->created_at)) {
            throw ValidationException::withMessages([
                'token' => ['Token inválido ou expirado.'],
            ]);
        }

        $user = User::query()->where('email', $data->email)->firstOrFail();
        $user->update(['password' => $data->password]);

        DB::table('password_reset_tokens')
            ->where('email', $data->email)
            ->where('tenant_id', Tenant::current()->getKey())
            ->delete();
    }
}
```

Save to `app/Domain/Auth/Services/ResetPasswordService.php`.

- [ ] **Step 10: Add `forgotPassword` and `resetPassword` to `AuthController`**

Add these two methods to the existing `AuthController` (after the `me` method):

```php
public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordService $service): JsonResponse
{
    $data = ForgotPasswordData::from($request->validated());
    $service->handle($data);

    return $this->success([], 'Se este e-mail estiver cadastrado, você receberá um link em breve.');
}

public function resetPassword(ResetPasswordRequest $request, ResetPasswordService $service): JsonResponse
{
    $data = ResetPasswordData::from($request->validated());
    $service->handle($data);

    return $this->success([], 'Senha redefinida com sucesso.');
}
```

Also add the imports at the top of the file:
```php
use App\Domain\Auth\Data\ForgotPasswordData;
use App\Domain\Auth\Data\ResetPasswordData;
use App\Domain\Auth\Services\ForgotPasswordService;
use App\Domain\Auth\Services\ResetPasswordService;
use App\Http\Requests\Api\V1\Dashboard\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\ResetPasswordRequest;
```

- [ ] **Step 11: Add routes to `routes/api.php`**

Inside the `ensure_tenant` middleware group, before the `auth:sanctum` group:

```php
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
```

- [ ] **Step 12: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 13: Run PasswordResetTest**

```bash
vendor/bin/sail artisan test --compact --filter=PasswordResetTest
```

Expected: 5 tests pass.

- [ ] **Step 14: Run full suite to check for regressions**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all 50 tests pass.

- [ ] **Step 15: Commit**

```bash
git add app/Domain/Auth/Data/ForgotPasswordData.php app/Domain/Auth/Data/ResetPasswordData.php app/Http/Requests/Api/V1/Dashboard/Auth/ForgotPasswordRequest.php app/Http/Requests/Api/V1/Dashboard/Auth/ResetPasswordRequest.php app/Domain/Auth/Services/ForgotPasswordService.php app/Domain/Auth/Services/ResetPasswordService.php app/Notifications/Auth/ForgotPasswordNotification.php app/Http/Controllers/Api/V1/Dashboard/AuthController.php routes/api.php tests/Feature/PasswordResetTest.php
git commit -m "feat: add forgot/reset password flow (tenant-scoped)"
```

---

### Task 3: Email Verification

**Files:**
- Modify: `app/Models/User.php`
- Create: `app/Notifications/Auth/VerifyEmailNotification.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Domain/Auth/Services/LoginUserService.php`
- Modify: `app/Domain/Auth/Services/RegisterTenantService.php`
- Create: `app/Domain/Auth/Services/VerifyEmailService.php`
- Modify: `app/Http/Controllers/Api/V1/Dashboard/AuthController.php`
- Modify: `routes/api.php`
- Create: `tests/Feature/EmailVerificationTest.php`

**Interfaces:**
- Consumes: `User::hasVerifiedEmail()`, `User::markEmailAsVerified()`, `User::sendEmailVerificationNotification()`
- Produces: `POST /api/v1/email/resend` → 200, `GET /api/v1/email/verify/{id}/{hash}` → 200, login blocked for unverified users

- [ ] **Step 1: Write failing `EmailVerificationTest`**

```bash
vendor/bin/sail artisan make:test --pest EmailVerificationTest --no-interaction
```

Replace `tests/Feature/EmailVerificationTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

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

    Notification::assertSentTo($user, \App\Notifications\Auth\VerifyEmailNotification::class);
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
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

    postJson('/api/v1/register', [
        'tenant_name' => 'New Corp',
        'tenant_slug' => 'new-corp',
        'name' => 'Admin',
        'email' => 'admin@new.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::withoutGlobalScopes()->where('email', 'admin@new.com')->first();
    Notification::assertSentTo($user, \App\Notifications\Auth\VerifyEmailNotification::class);
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=EmailVerificationTest
```

Expected: fails — `MustVerifyEmail` not implemented.

- [ ] **Step 3: Update `User` model — add `MustVerifyEmail`**

Add `Illuminate\Contracts\Auth\MustVerifyEmail` to implements list and add `Illuminate\Auth\MustVerifyEmail` trait:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatusEnum;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, HasMedia, MustVerifyEmailContract
{
    use BelongsToTenant;

    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use HasUuid;
    use InteractsWithMedia;
    use MustVerifyEmail;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'email_verified_at',
        'password',
        'status',
        'tenant_id',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\Auth\VerifyEmailNotification);
    }
}
```

- [ ] **Step 4: Create `VerifyEmailNotification`**

```php
<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifique seu endereço de e-mail')
            ->greeting('Olá!')
            ->line('Clique no botão abaixo para verificar seu endereço de e-mail.')
            ->action('Verificar e-mail', $url)
            ->line('Este link expira em 60 minutos.')
            ->line('Se você não criou uma conta, ignore este e-mail.');
    }
}
```

Save to `app/Notifications/Auth/VerifyEmailNotification.php`.

- [ ] **Step 5: Update `AppServiceProvider::boot()` — configure VerifyEmail URL and notification**

```php
<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/landlord'));

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            HorizonCheck::new(),
            QueueCheck::new(),
        ]);

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}
```

- [ ] **Step 6: Update `LoginUserService` — block unverified users**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\LoginUserData;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    public function handle(LoginUserData $data): array
    {
        $user = User::query()
            ->where('email', $data->email)
            ->first();

        if (! $user || $user->status === UserStatusEnum::Inactive) {
            throw new AuthenticationException('Credenciais inválidas');
        }

        if (! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('Credenciais inválidas');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('E-mail não verificado. Verifique sua caixa de entrada.');
        }

        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
```

- [ ] **Step 7: Update `RegisterTenantService` — send verification after user creation**

Add `$user->sendEmailVerificationNotification();` after `$user->assignRole('admin');`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\RegisterData;
use App\Enums\TenantStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterTenantService
{
    public function handle(RegisterData $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data->tenant_name,
                'slug' => $data->tenant_slug,
                'status' => TenantStatusEnum::Active,
            ]);

            $tenant->makeCurrent();

            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'status' => UserStatusEnum::Active,
            ]);

            $user->assignRole('admin');
            $user->sendEmailVerificationNotification();

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'tenant' => $tenant->fresh(),
                'user' => $user->fresh(),
                'token' => $token,
            ];
        }, 3);
    }
}
```

- [ ] **Step 8: Create `VerifyEmailService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Models\User;

class VerifyEmailService
{
    public function resend(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }

    public function verify(User $user, string $hash): bool
    {
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }
}
```

Save to `app/Domain/Auth/Services/VerifyEmailService.php`.

- [ ] **Step 9: Add `verifyEmail` and `resendVerification` to `AuthController`**

Add these two methods (after `resetPassword`):

```php
public function resendVerification(Request $request, VerifyEmailService $service): JsonResponse
{
    $service->resend($request->user());

    return $this->success([], 'E-mail de verificação reenviado.');
}

public function verifyEmail(Request $request, string $id, string $hash, VerifyEmailService $service): JsonResponse
{
    if (! $request->hasValidSignature()) {
        return $this->error('Link de verificação inválido ou expirado.', 403);
    }

    $user = \App\Models\User::withoutGlobalScopes()->findOrFail($id);

    if (! $service->verify($user, $hash)) {
        return $this->error('Link de verificação inválido.', 403);
    }

    return $this->success([], 'E-mail verificado com sucesso.');
}
```

Add imports:
```php
use App\Domain\Auth\Services\VerifyEmailService;
```

- [ ] **Step 10: Add routes to `routes/api.php`**

Inside the `ensure_tenant` group, before `auth:sanctum` group, add:

```php
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');
```

Inside the `auth:sanctum` group, add:

```php
Route::post('email/resend', [AuthController::class, 'resendVerification']);
```

- [ ] **Step 11: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 12: Run EmailVerificationTest**

```bash
vendor/bin/sail artisan test --compact --filter=EmailVerificationTest
```

Expected: 5 tests pass.

- [ ] **Step 13: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass. Note: `TenantRegistrationTest` has a test that logs in after registration — it may now fail because the registered user has unverified email. If it fails, update that test's registered user login assertion to expect a 401, OR update the test to mark email as verified before logging in.

- [ ] **Step 14: Commit**

```bash
git add app/Models/User.php app/Notifications/Auth/VerifyEmailNotification.php app/Providers/AppServiceProvider.php app/Domain/Auth/Services/LoginUserService.php app/Domain/Auth/Services/RegisterTenantService.php app/Domain/Auth/Services/VerifyEmailService.php app/Http/Controllers/Api/V1/Dashboard/AuthController.php routes/api.php tests/Feature/EmailVerificationTest.php
git commit -m "feat: add email verification — blocks login, sends on register, resend/verify endpoints"
```

---

### Task 4: User Invite + Accept

**Files:**
- Create: `app/Domain/Auth/Data/InviteUserData.php`
- Create: `app/Domain/Auth/Data/AcceptInviteData.php`
- Create: `app/Http/Requests/Api/V1/Dashboard/Auth/InviteUserRequest.php`
- Create: `app/Http/Requests/Api/V1/Dashboard/Auth/AcceptInviteRequest.php`
- Create: `app/Domain/Auth/Services/InviteUserService.php`
- Create: `app/Domain/Auth/Services/AcceptInviteService.php`
- Create: `app/Notifications/Auth/InviteUserNotification.php`
- Create: `app/Http/Controllers/Api/V1/Dashboard/Admin/AdminUserController.php`
- Modify: `app/Http/Controllers/Api/V1/Dashboard/AuthController.php`
- Modify: `routes/api.php`
- Create: `tests/Feature/UserInviteTest.php`

**Interfaces:**
- Consumes: `UserInvitation` model (Task 1), `UserStatusEnum::Pending` (Task 1), `UserStatusEnum::Active`
- Produces: `POST /api/v1/admin/users/invite` → 201, `POST /api/v1/invite/accept` → 200 with token

- [ ] **Step 1: Write failing `UserInviteTest`**

```bash
vendor/bin/sail artisan make:test --pest UserInviteTest --no-interaction
```

Replace `tests/Feature/UserInviteTest.php`:

```php
<?php

use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
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

    Notification::assertSentTo($invitedUser, \App\Notifications\Auth\InviteUserNotification::class);
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
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
vendor/bin/sail artisan test --compact --filter=UserInviteTest
```

Expected: fails — routes not found.

- [ ] **Step 3: Create `InviteUserData`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Data;

class InviteUserData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,
    ) {}
}
```

Save to `app/Domain/Auth/Data/InviteUserData.php`.

- [ ] **Step 4: Create `AcceptInviteData`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class AcceptInviteData extends Data
{
    public function __construct(
        public readonly string $token,
        #[Hidden]
        public readonly string $password,
    ) {}
}
```

Save to `app/Domain/Auth/Data/AcceptInviteData.php`.

- [ ] **Step 5: Create `InviteUserRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', auth()->user()->tenant_id)),
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'user'])],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado neste tenant.',
            'role.required' => 'O papel é obrigatório.',
            'role.in' => 'O papel deve ser admin ou user.',
        ];
    }
}
```

Save to `app/Http/Requests/Api/V1/Dashboard/Auth/InviteUserRequest.php`.

- [ ] **Step 6: Create `AcceptInviteRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AcceptInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'O token de convite é obrigatório.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
        ];
    }
}
```

Save to `app/Http/Requests/Api/V1/Dashboard/Auth/AcceptInviteRequest.php`.

- [ ] **Step 7: Create `InviteUserNotification`**

```php
<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $tenantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url').'/invite/accept?token='.$this->token;

        return (new MailMessage)
            ->subject('Você foi convidado para '.$this->tenantName)
            ->greeting('Olá!')
            ->line('Você foi convidado para acessar '.$this->tenantName.'.')
            ->line('Clique no botão abaixo para criar sua senha e ativar sua conta.')
            ->action('Aceitar convite', $url)
            ->line('Este convite expira em 24 horas.')
            ->line('Se você não esperava este convite, ignore este e-mail.');
    }
}
```

Save to `app/Notifications/Auth/InviteUserNotification.php`.

- [ ] **Step 8: Create `InviteUserService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\InviteUserData;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\Auth\InviteUserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteUserService
{
    public function handle(InviteUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::current();

            $user = User::create([
                'name' => explode('@', $data->email)[0],
                'email' => $data->email,
                'password' => Str::random(32),
                'status' => UserStatusEnum::Pending,
            ]);

            $user->assignRole($data->role);

            $invitation = UserInvitation::create([
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->id,
                'token' => Str::uuid()->toString(),
                'expires_at' => now()->addHours(24),
            ]);

            $user->notify(new InviteUserNotification($invitation->token, $tenant->name));

            return $user->fresh();
        }, 3);
    }
}
```

Save to `app/Domain/Auth/Services/InviteUserService.php`.

- [ ] **Step 9: Create `AcceptInviteService`**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\AcceptInviteData;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInviteService
{
    public function handle(AcceptInviteData $data): array
    {
        $invitation = UserInvitation::where('token', $data->token)
            ->where('tenant_id', Tenant::current()->getKey())
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['Convite inválido.'],
            ]);
        }

        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages([
                'token' => ['Convite já utilizado.'],
            ]);
        }

        if (now()->isAfter($invitation->expires_at)) {
            throw ValidationException::withMessages([
                'token' => ['Convite expirado.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $data) {
            $user = $invitation->user;

            $user->update([
                'password' => $data->password,
                'email_verified_at' => now(),
                'status' => UserStatusEnum::Active,
            ]);

            $invitation->update(['accepted_at' => now()]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->fresh(),
                'token' => $token,
            ];
        }, 3);
    }
}
```

Save to `app/Domain/Auth/Services/AcceptInviteService.php`.

- [ ] **Step 10: Create `AdminUserController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard\Admin;

use App\Domain\Auth\Data\InviteUserData;
use App\Domain\Auth\Services\InviteUserService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Auth\InviteUserRequest;
use App\Http\Resources\Api\V1\Dashboard\User\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin
 */
class AdminUserController extends ApiController
{
    public function invite(InviteUserRequest $request, InviteUserService $service): JsonResponse
    {
        $data = InviteUserData::from($request->validated());
        $user = $service->handle($data);

        return $this->created(
            ['user' => new UserResource($user)],
            'Convite enviado com sucesso.'
        );
    }
}
```

Save to `app/Http/Controllers/Api/V1/Dashboard/Admin/AdminUserController.php`.

- [ ] **Step 11: Add `acceptInvite` to `AuthController`**

Add this method after `resendVerification`:

```php
public function acceptInvite(AcceptInviteRequest $request, AcceptInviteService $service): JsonResponse
{
    $data = AcceptInviteData::from($request->validated());
    $result = $service->handle($data);

    return $this->success([
        'user' => new UserResource($result['user']),
        'token' => $result['token'],
    ], 'Conta ativada com sucesso.');
}
```

Add imports:
```php
use App\Domain\Auth\Data\AcceptInviteData;
use App\Domain\Auth\Services\AcceptInviteService;
use App\Http\Requests\Api\V1\Dashboard\Auth\AcceptInviteRequest;
```

- [ ] **Step 12: Update `routes/api.php` — add invite routes**

Add inside `ensure_tenant` group (before `auth:sanctum` group):

```php
Route::post('invite/accept', [AuthController::class, 'acceptInvite']);
```

Add inside the admin group:

```php
use App\Http\Controllers\Api\V1\Dashboard\Admin\AdminUserController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('users/invite', [AdminUserController::class, 'invite']);
});
```

- [ ] **Step 13: Format**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 14: Run `UserInviteTest`**

```bash
vendor/bin/sail artisan test --compact --filter=UserInviteTest
```

Expected: 6 tests pass.

- [ ] **Step 15: Run full suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 16: Commit**

```bash
git add app/Domain/Auth/Data/InviteUserData.php app/Domain/Auth/Data/AcceptInviteData.php app/Http/Requests/Api/V1/Dashboard/Auth/InviteUserRequest.php app/Http/Requests/Api/V1/Dashboard/Auth/AcceptInviteRequest.php app/Domain/Auth/Services/InviteUserService.php app/Domain/Auth/Services/AcceptInviteService.php app/Notifications/Auth/InviteUserNotification.php app/Http/Controllers/Api/V1/Dashboard/Admin/AdminUserController.php app/Http/Controllers/Api/V1/Dashboard/AuthController.php routes/api.php tests/Feature/UserInviteTest.php
git commit -m "feat: add user invite flow — admin invite, accept invite, role assignment"
```
