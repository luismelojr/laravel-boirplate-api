# Multi-Tenancy — Design Spec

**Date:** 2026-06-18
**Status:** Approved
**Package:** `spatie/laravel-multitenancy` v3

---

## Overview

Add single-database multi-tenancy to the Laravel 13 API boilerplate. Each tenant is an isolated workspace identified by a `tenant_id` column on all scoped tables. Tenants are resolved per-request via the `X-Tenant-ID` header (UUID).

**User model:** one user belongs to exactly one tenant. No pivot table.

---

## 1. Database & Models

### `tenants` table

```
id (PK), uuid (unique), name (string), slug (unique), status (default: active), timestamps, softDeletes
```

### `users` table

- Add `tenant_id` (FK → `tenants.id`) column via new migration (not modifying original migration)

### `Tenant` model

- `App\Models\Tenant`
- Uses `HasUuid`, `SoftDeletes`
- Implements `Spatie\Multitenancy\Models\Tenant` (extends the package's base model)
- Casts: `status` → `TenantStatusEnum` (Active/Inactive)
- Route key: `uuid`
- Factory + Seeder

### `User` model

- Add `Spatie\Multitenancy\Concerns\UsesLandlordConnection` — NOT needed (single DB)
- Add `BelongsToTenant` trait — injects global scope `WHERE tenant_id = current_tenant_id` and auto-sets `tenant_id` on create
- Remove `tenant_id` from `$fillable` (set automatically by trait)

### New Enum

`App\Enums\TenantStatusEnum` — `Active = 'active'`, `Inactive = 'inactive'`, with `values(): array`

---

## 2. Tenant Resolution

### `App\Domain\Tenant\Finders\HeaderTenantFinder`

Implements `Spatie\Multitenancy\TenantFinder\TenantFinder`.

```php
public function findForRequest(Request $request): ?Tenant
{
    $uuid = $request->header('X-Tenant-ID');
    if (! $uuid) return null;
    return Tenant::where('uuid', $uuid)->where('status', TenantStatusEnum::Active)->first();
}
```

Returns `null` if header is absent, UUID doesn't exist, or tenant is inactive.

### `config/multitenancy.php`

- `tenant_finder` → `HeaderTenantFinder::class`
- `tenant_model` → `App\Models\Tenant::class`
- `switch_tenant_tasks` → default package tasks

### Middleware

`NeedsTenant` (from package) added to all API routes **except** `POST /api/v1/register`.

If `HeaderTenantFinder` returns `null`, `NeedsTenant` aborts — we override its behavior in `ApiExceptionRegister` to return `{ success: false, message: 'Tenant não encontrado ou inativo' }` with HTTP 422.

### `bootstrap/app.php`

Register `NeedsTenant` middleware alias. Apply to the API tenant group.

### Route structure

```
POST /api/v1/register          → public (no tenant required)
POST /api/v1/login             → NeedsTenant + throttle:auth
POST /api/v1/logout            → NeedsTenant + auth:sanctum
GET  /api/v1/me                → NeedsTenant + auth:sanctum
(admin group)                  → NeedsTenant + auth:sanctum + role:admin
```

---

## 3. Registration Flow

### `POST /api/v1/register` (public — no tenant header needed)

Creates tenant + admin user atomically.

**Request body:**
```json
{
  "tenant_name": "Acme Corp",
  "tenant_slug": "acme-corp",
  "name": "João Silva",
  "email": "joao@acme.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

**Validation (`RegisterRequest`):**
- `tenant_name`: required, string, max 255
- `tenant_slug`: required, string, max 100, regex `/^[a-z0-9-]+$/`, unique in `tenants`
- `name`: required, string, max 255
- `email`: required, email, max 255 (unique per tenant — validated after tenant creation inside transaction)
- `password`: required, string, min 8, confirmed
- All messages in Portuguese

**DTO:** `App\Domain\Auth\Data\RegisterData extends Data`

**Service:** `App\Domain\Auth\Services\RegisterTenantService::handle(RegisterData): array`

```
DB::transaction(fn() => {
    1. Create Tenant (name, slug, status: active)
    2. $tenant->makeCurrent()
    3. Create User (name, email, password, status: active)
    4. $user->assignRole('admin')
    5. $token = $user->createToken('auth_token')->plainTextToken
    return ['tenant' => $tenant, 'user' => $user, 'token' => $token]
}, 3)
```

**Response:** HTTP 201 via `$this->created([...])`:
```json
{
  "success": true,
  "message": "Tenant criado com sucesso",
  "data": {
    "tenant": { "uuid", "name", "slug", "status" },
    "user": { "uuid", "name", "email", "status", "avatar_url" },
    "token": "..."
  }
}
```

**New Resources:**
- `TenantResource` — uuid, name, slug, status, created_at

---

## 4. Tests

### Helper in `tests/Pest.php`

```php
function tenantActingAs(User $user): User
{
    $user->tenant->makeCurrent();
    Sanctum::actingAs($user);
    return $user;
}
```

### Update all existing tests

`AuthTest`, `RoleTest`, `AvatarTest` — each test that creates a `User` must:
1. `$tenant = Tenant::factory()->create()`
2. `$tenant->makeCurrent()`
3. Create user (BelongsToTenant sets tenant_id automatically)
4. Add `'X-Tenant-ID' => $tenant->uuid` to all HTTP requests via `withHeader()` or use the `tenantActingAs()` helper

### `TenantTest` (new)

- `POST /register` creates tenant + user + token (201)
- `POST /register` with duplicate slug → 422
- `POST /login` without `X-Tenant-ID` header → 422
- `POST /login` with invalid UUID in header → 422
- `POST /login` with inactive tenant → 422
- Isolation: user from tenant A cannot see user from tenant B (global scope test)
- Register assigns `admin` role to first user

---

## Implementation Order

| Step | Task |
|------|------|
| 1 | Install `spatie/laravel-multitenancy`, publish config |
| 2 | Create `TenantStatusEnum` |
| 3 | Create `Tenant` model, migration, factory |
| 4 | `HeaderTenantFinder` |
| 5 | Migration: add `tenant_id` to `users` |
| 6 | Update `User` model (`BelongsToTenant`) |
| 7 | Register middleware alias, update route groups |
| 8 | Handle tenant-not-found in `ApiExceptionRegister` |
| 9 | `RegisterData`, `RegisterRequest`, `RegisterTenantService`, `AuthController@register`, `TenantResource` |
| 10 | Update `tests/Pest.php` helper + update all existing tests |
| 11 | Write `TenantTest` |
