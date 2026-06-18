<?php

use App\Enums\TenantStatusEnum;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a tenant with uuid automatically', function () {
    $tenant = Tenant::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);

    expect($tenant->uuid)->not->toBeEmpty();
    expect($tenant->name)->toBe('Acme Corp');
    expect($tenant->slug)->toBe('acme-corp');
    expect($tenant->status)->toBe(TenantStatusEnum::Active);
});

it('casts status to TenantStatusEnum', function () {
    $tenant = Tenant::factory()->create();
    expect($tenant->status)->toBeInstanceOf(TenantStatusEnum::class);
});

it('can set current tenant and retrieve it', function () {
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    expect(Tenant::current()->uuid)->toBe($tenant->uuid);
});

it('creates inactive tenant via factory state', function () {
    $tenant = Tenant::factory()->inactive()->create();
    expect($tenant->status)->toBe(TenantStatusEnum::Inactive);
});
