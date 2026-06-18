# Auth Completo — Design Spec

**Date:** 2026-06-18
**Status:** Approved

---

## Overview

Adiciona três fluxos de autenticação ao boilerplate Laravel 13 SaaS multi-tenant:

1. **Reset de senha** — forgot/reset com token de 1h, tenant-scoped
2. **Verificação de e-mail** — obrigatória para login, disparada no registro
3. **Convite de usuário** — admin convida membros ao tenant, token de 24h

Todos os fluxos exigem `X-Tenant-ID` header. Mensagens em português. E-mail via Mailpit (local) e `resend/resend-laravel` (produção).

---

## 1. Reset de Senha

### Endpoints

| Método | Rota | Middleware |
|--------|------|-----------|
| POST | `/api/v1/forgot-password` | `ensure_tenant` |
| POST | `/api/v1/reset-password` | `ensure_tenant` |

### `POST /api/v1/forgot-password`

**Request:** `{ email }`

**Fluxo:**
1. Busca usuário por `email` no tenant atual
2. Se não encontrado — retorna 200 mesmo assim (evita user enumeration)
3. Se encontrado — gera token (60 chars random), salva em `password_reset_tokens` com `tenant_id` e `created_at`, envia `ForgotPasswordNotification`

**Response:** HTTP 200 `{ success: true, message: 'Se este e-mail estiver cadastrado, você receberá um link em breve.' }`

### `POST /api/v1/reset-password`

**Request:** `{ token, email, password, password_confirmation }`

**Validação:**
- `token`: required, string
- `email`: required, email
- `password`: required, min:8, confirmed
- Mensagens em português

**Fluxo:**
1. Busca `password_reset_tokens` WHERE `email = ? AND tenant_id = current_tenant_id`
2. Se não encontrado → 422 "Token inválido ou expirado"
3. Se `created_at` > 1h atrás → 422 "Token inválido ou expirado"
4. Atualiza senha do usuário via `User::where('email', $email)->first()->update(['password' => $password])`
5. Deleta o token da tabela

**Response:** HTTP 200 `{ success: true, message: 'Senha redefinida com sucesso.' }`

### Migration

Adicionar `tenant_id` à tabela `password_reset_tokens`:

```sql
ALTER TABLE password_reset_tokens ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL AFTER email;
ALTER TABLE password_reset_tokens DROP PRIMARY KEY;
ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email, tenant_id);
```

### Notificação

`App\Notifications\Auth\ForgotPasswordNotification` — envia link:
`{APP_URL}/reset-password?token={token}&email={email}`

Assunto: "Redefinição de senha", corpo em português.

### Domain

- `App\Domain\Auth\Data\ForgotPasswordData` (email)
- `App\Domain\Auth\Data\ResetPasswordData` (token, email, password)
- `App\Http\Requests\Api\V1\Dashboard\Auth\ForgotPasswordRequest`
- `App\Http\Requests\Api\V1\Dashboard\Auth\ResetPasswordRequest`
- `App\Domain\Auth\Services\ForgotPasswordService`
- `App\Domain\Auth\Services\ResetPasswordService`

---

## 2. Verificação de E-mail

### Mudanças no `User` model

`User` implementa `Illuminate\Contracts\Auth\MustVerifyEmail`.

### Bloqueio no Login

`LoginUserService::handle()` — após validar credenciais e status, verifica e-mail:

```php
if (! $user->hasVerifiedEmail()) {
    throw new AuthenticationException('E-mail não verificado. Verifique sua caixa de entrada.');
}
```

### Disparo automático no Registro

`RegisterTenantService` — após criar o usuário, chama `$user->sendEmailVerificationNotification()`.

### Endpoints

| Método | Rota | Middleware |
|--------|------|-----------|
| POST | `/api/v1/email/resend` | `ensure_tenant + auth:sanctum` |
| GET | `/api/v1/email/verify/{id}/{hash}` | `ensure_tenant` |

### `POST /api/v1/email/resend`

Reenvia a notificação de verificação para `$request->user()`. Retorna 200.

### `GET /api/v1/email/verify/{id}/{hash}`

URL assinada (gerada por `URL::temporarySignedRoute`). Valida `id` e `hash` SHA1 do e-mail. Chama `$user->markEmailAsVerified()`. Retorna 200.

### Notificação

`App\Notifications\Auth\VerifyEmailNotification` — estende `Illuminate\Auth\Notifications\VerifyEmail`. Customiza o assunto e corpo em português.

Configuração: sobrescreve `VerifyEmail::createUrlUsing()` em `AppServiceProvider::boot()` para gerar URL apontando para a rota da API.

### Domain

- `App\Domain\Auth\Services\VerifyEmailService`

---

## 3. Convite de Usuário

### Nova tabela `user_invitations`

```
id (PK)
tenant_id (FK → tenants.id, cascade delete)
user_id (FK → users.id, cascade delete)
token (uuid, unique)
expires_at (timestamp)
accepted_at (timestamp, nullable)
timestamps
```

### Endpoints

| Método | Rota | Middleware |
|--------|------|-----------|
| POST | `/api/v1/admin/users/invite` | `ensure_tenant + auth:sanctum + role:admin` |
| POST | `/api/v1/invite/accept` | `ensure_tenant` |

### `POST /api/v1/admin/users/invite`

**Request:** `{ email, role }` (role: `admin` ou `user`, default `user`)

**Validação:**
- `email`: required, email, único dentro do tenant atual
- `role`: required, in:admin,user
- Mensagens em português

**Fluxo:**
1. Cria `User` com `status: pending`, `email_verified_at: null`, password vazia (será definida no accept)
2. Atribui role ao usuário
3. Cria `UserInvitation` com `token = Str::uuid()`, `expires_at = now()->addHours(24)`
4. Envia `InviteUserNotification`

**Response:** HTTP 201 `{ success: true, message: 'Convite enviado com sucesso.', data: { user: UserResource } }`

### `POST /api/v1/invite/accept`

**Request:** `{ token, password, password_confirmation }`

**Validação:**
- `token`: required, string
- `password`: required, min:8, confirmed
- Mensagens em português

**Fluxo:**
1. Busca `UserInvitation` WHERE `token = ? AND tenant_id = current_tenant_id`
2. Se não encontrado → 422 "Convite inválido"
3. Se `accepted_at` não é null → 422 "Convite já utilizado"
4. Se `expires_at` < now() → 422 "Convite expirado"
5. `DB::transaction`:
   - Atualiza User: `password`, `email_verified_at = now()`, `status: active`
   - Atualiza Invitation: `accepted_at = now()`
   - Cria token Sanctum

**Response:** HTTP 200 `{ success: true, message: 'Conta ativada com sucesso.', data: { user, token } }`

### Notificação

`App\Notifications\Auth\InviteUserNotification` — envia link:
`{APP_URL}/invite/accept?token={token}`

Assunto: "Você foi convidado para {tenant.name}", corpo em português.

### Domain

- `App\Models\UserInvitation`
- `App\Domain\Auth\Data\InviteUserData` (email, role)
- `App\Domain\Auth\Data\AcceptInviteData` (token, password)
- `App\Http\Requests\Api\V1\Dashboard\Auth\InviteUserRequest`
- `App\Http\Requests\Api\V1\Dashboard\Auth\AcceptInviteRequest`
- `App\Domain\Auth\Services\InviteUserService`
- `App\Domain\Auth\Services\AcceptInviteService`
- `App\Http\Resources\Api\V1\Dashboard\User\UserInvitationResource`

---

## 4. Rotas Completas

```php
// Reset de senha (público + tenant)
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// Verificação de e-mail
Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail']);
Route::post('email/resend', [AuthController::class, 'resendVerification'])
    ->middleware('auth:sanctum');

// Convite
Route::post('invite/accept', [AuthController::class, 'acceptInvite']);

// Admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('users/invite', [AdminUserController::class, 'invite']);
});
```

Todos dentro do grupo `ensure_tenant`.

### Novo Controller

`App\Http\Controllers\Api\V1\Dashboard\Admin\AdminUserController` — para rotas admin de usuários.

---

## 5. Testes

### `PasswordResetTest`
- Forgot password com e-mail existente envia notificação
- Forgot password com e-mail inexistente retorna 200 (sem vazar info)
- Reset com token válido atualiza senha
- Reset com token expirado → 422
- Reset com token inválido → 422
- Após reset, login com nova senha funciona

### `EmailVerificationTest`
- Login bloqueado sem verificação → 401
- Resend envia notificação
- Verify URL válida marca `email_verified_at`
- Verify URL inválida/hash incorreto → 403
- Após registro do tenant, notificação de verificação é disparada

### `UserInviteTest`
- Admin convida usuário → 201, notificação enviada
- Não-admin não pode convidar → 403
- Aceitar convite válido define senha + loga + verifica e-mail
- Aceitar token expirado → 422
- Aceitar token já utilizado → 422
- Usuário convidado recebe role correta

---

## 6. Ordem de Implementação

| # | Tarefa |
|---|--------|
| 1 | Migration: `tenant_id` em `password_reset_tokens` + migration `user_invitations` + `UserInvitation` model |
| 2 | Forgot/Reset password (ForgotPasswordService, ResetPasswordService, requests, notificação, rotas) |
| 3 | Email verification (MustVerifyEmail em User, bloqueio no login, resend/verify endpoints, notificação, RegisterTenantService atualizado) |
| 4 | User invite (InviteUserService, AcceptInviteService, AdminUserController, requests, notificação, rotas) |
| 5 | Testes completos para todos os fluxos |
