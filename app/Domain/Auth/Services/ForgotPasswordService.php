<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\ForgotPasswordData;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Auth\ForgotPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgotPasswordService
{
    public function handle(ForgotPasswordData $data): void
    {
        $user = User::query()->where('email', $data->email)->first();

        if (! $user) {
            return;
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')
            ->upsert(
                [
                    'email' => $data->email,
                    'tenant_id' => Tenant::current()->getKey(),
                    'token' => $token,
                    'created_at' => now(),
                ],
                ['email', 'tenant_id'],
                ['token', 'created_at']
            );

        $user->notify(new ForgotPasswordNotification($token));
    }
}
