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

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'tenant' => $tenant->fresh(),
                'user' => $user->fresh(),
                'token' => $token,
            ];
        }, 3);
    }
}
