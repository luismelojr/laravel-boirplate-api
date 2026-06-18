<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Finders;

use App\Enums\TenantStatusEnum;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $uuid = $request->header('X-Tenant-ID');

        if (! $uuid) {
            return null;
        }

        return Tenant::query()
            ->where('uuid', $uuid)
            ->where('status', TenantStatusEnum::Active)
            ->first();
    }
}
