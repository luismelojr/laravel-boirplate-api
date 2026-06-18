<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => config('app.admin_tenant_slug')],
            [
                'name' => config('app.admin_tenant_name'),
                'status' => 'active',
            ]
        );

        $tenant->makeCurrent();
    }
}
