<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\AcceptInviteData;
use App\Enums\UserStatusEnum;
use App\Models\Tenant;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInviteService
{
    public function handle(AcceptInviteData $data): array
    {
        $invitation = UserInvitation::where('token', $data->token)
            ->where('tenant_id', Tenant::current()->getKey())
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['Convite inválido.'],
            ]);
        }

        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages([
                'token' => ['Convite já utilizado.'],
            ]);
        }

        if (now()->isAfter($invitation->expires_at)) {
            throw ValidationException::withMessages([
                'token' => ['Convite expirado.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $data) {
            $user = $invitation->user;

            $user->update([
                'password' => $data->password,
                'email_verified_at' => now(),
                'status' => UserStatusEnum::Active,
            ]);

            $invitation->update(['accepted_at' => now()]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->fresh(),
                'token' => $token,
            ];
        }, 3);
    }
}
