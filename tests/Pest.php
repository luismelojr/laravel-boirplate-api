<?php

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Authenticate as a user AND make their tenant current.
 * Use this in every test that calls a tenant-scoped API route.
 */
function tenantActingAs(User $user): User
{
    $tenant = $user->tenant;

    if (! $tenant) {
        throw new RuntimeException("User [{$user->uuid}] has no tenant. Call \$tenant->makeCurrent() before creating the user.");
    }

    $tenant->makeCurrent();
    Sanctum::actingAs($user);

    return $user;
}
