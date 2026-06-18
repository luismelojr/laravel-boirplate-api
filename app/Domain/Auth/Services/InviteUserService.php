<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\InviteUserData;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\Auth\InviteUserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteUserService
{
    public function handle(InviteUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::current();

            $user = User::create([
                'name' => explode('@', $data->email)[0],
                'email' => $data->email,
                'password' => Str::random(32),
                'status' => UserStatusEnum::Pending,
            ]);

            $user->assignRole($data->role);

            $invitation = UserInvitation::create([
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->id,
                'token' => Str::uuid()->toString(),
                'expires_at' => now()->addHours(24),
            ]);

            $user->notify(new InviteUserNotification($invitation->token, $tenant->name));

            return $user->fresh();
        }, 3);
    }
}
