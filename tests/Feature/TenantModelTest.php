<?php

use App\Domain\Tenant\Finders\HeaderTenantFinder;
use App\Enums\TenantStatusEnum;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

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

it('HeaderTenantFinder resolves tenant from X-Tenant-ID header', function () {
    $tenant = Tenant::factory()->create();

    $request = Request::create('/');
    $request->headers->set('X-Tenant-ID', $tenant->uuid);

    $finder = new HeaderTenantFinder;
    $found = $finder->findForRequest($request);

    expect($found->uuid)->toBe($tenant->uuid);
});

it('HeaderTenantFinder returns null when header is missing', function () {
    $request = Request::create('/');

    $finder = new HeaderTenantFinder;

    expect($finder->findForRequest($request))->toBeNull();
});

it('HeaderTenantFinder returns null for inactive tenant', function () {
    $tenant = Tenant::factory()->inactive()->create();

    $request = Request::create('/');
    $request->headers->set('X-Tenant-ID', $tenant->uuid);

    $finder = new HeaderTenantFinder;

    expect($finder->findForRequest($request))->toBeNull();
});
