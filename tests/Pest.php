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
    $user->tenant->makeCurrent();
    Sanctum::actingAs($user);

    return $user;
}
