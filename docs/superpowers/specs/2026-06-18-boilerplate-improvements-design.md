# Boilerplate Improvements — Design Spec

**Date:** 2026-06-18
**Status:** Approved

---

## Overview

Six targeted improvements to the Laravel 13 API boilerplate, focused on Spatie packages and code quality. No breaking changes to the existing auth flow.

---

## 1. `spatie/laravel-data` — DTO Layer

**Goal:** Replace manual readonly DTOs with `spatie/laravel-data` for automatic casting, transformation, and a consistent base class.

**Changes:**
- `LoginUserData` drops the manual `from()` method and extends `Spatie\LaravelData\Data`
- `Data::from($array)` handles construction automatically
- Establishes the pattern for all future Domain Data objects

**Not in scope:** API Resources (`UserResource`) remain as `JsonResource` — input and output concerns stay separate.

---

## 2. `spatie/laravel-permission` — RBAC

**Goal:** Add role-based access control with `admin` and `user` as the boilerplate roles.

**Changes:**
- Install package and run its migrations
- Add `HasRoles` trait to `User` model
- Create `RoleSeeder` with `admin` and `user` roles
- `DatabaseSeeder` calls `RoleSeeder`; test user in `UserSeeder` receives `admin` role
- Add a protected route group example in `api.php` using `middleware('role:admin')`
- `HorizonServiceProvider` and `TelescopeServiceProvider` gate logic switches from email whitelist to `$request->user()?->hasRole('admin')`

---

## 3. `spatie/laravel-media-library` — Avatar

**Goal:** Replace the `avatar_url` string column with a proper Media Collection.

**Changes:**
- New migration removes `avatar_url` column from `users` table
- `User` model implements `HasMedia`, uses `InteractsWithMedia` trait
- Defines `avatar` media collection with a `thumb` conversion (150×150)
- `UserResource` returns `$this->getFirstMediaUrl('avatar')` instead of `$this->avatar_url`
- Default disk: `public` (local). Production switches via `MEDIA_DISK=s3` in `.env`
- `UserFactory` updated to remove `avatar_url` from `definition()`

---

## 4. `spatie/laravel-health` — Health Endpoint

**Goal:** Expose `GET /health` for uptime monitors and load balancers.

**Checks:**
- `DatabaseCheck` — MySQL connection
- `RedisCheck` — Redis ping
- `HorizonCheck` — workers active
- `QueueCheck` — no jobs delayed beyond 5 minutes

**Changes:**
- Register checks in `AppServiceProvider` via `Health::checks([...])`
- Add `GET /health` route in `routes/web.php` (no auth middleware)
- Response format: standard `spatie/laravel-health` JSON, compatible with Oh Dear / Better Uptime

---

## 5. Mailpit — Local Email

**Goal:** Enable local email testing without leaving Docker.

**Changes:**
- Add `mailpit` service to `compose.yaml` (ports `1025` SMTP, `8025` dashboard)
- Update `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`
- Update `.env.example` with the same values
- Dashboard available at `http://localhost:8025`

---

## 6. `LoginUserService` — Anti-Pattern Fixes

**Goal:** Remove redundant error handling and use the Sanctum relation properly.

**Changes:**

1. Remove `try/catch` that only calls `report()` + rethrow — Laravel handles this automatically via `bootstrap/app.php`
2. Replace raw `DB::table('personal_access_tokens')` query with `$user->tokens()->where('name', 'auth_token')->delete()`

---

## Implementation Order

| Step | Task | Depends on |
|------|------|------------|
| 1 | Fix `LoginUserService` anti-patterns | — |
| 2 | Install `spatie/laravel-data`, update `LoginUserData` | — |
| 3 | Install `spatie/laravel-permission`, seeder, routes | `spatie/laravel-data` done |
| 4 | Add Mailpit to `compose.yaml` + `.env` | — |
| 5 | Install `spatie/laravel-media-library`, migrate `avatar_url` | — |
| 6 | Install `spatie/laravel-health`, register checks, add route | — |

Steps 1, 2, 4, 5, 6 are independent. Step 3 comes after step 2 so the permission seeder can use the same Data pattern if needed.

---

## Tests

Every change must be covered:
- `LoginUserService` fix: existing `AuthTest` must still pass
- `spatie/laravel-data`: existing auth tests cover `LoginUserData` indirectly
- `spatie/laravel-permission`: new `RoleTest` — assert roles exist, assert middleware blocks non-admin
- `spatie/laravel-media-library`: new `AvatarTest` — assert upload stores media, `UserResource` returns URL
- `spatie/laravel-health`: new `HealthTest` — assert `GET /health` returns 200 with all checks
- Mailpit: no automated test needed (infrastructure only)
