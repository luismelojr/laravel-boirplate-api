<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\ResetPasswordData;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetPasswordService
{
    public function handle(ResetPasswordData $data): void
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $data->email)
            ->where('tenant_id', Tenant::current()->getKey())
            ->where('token', $data->token)
            ->first();

        if (! $record || now()->subHour()->isAfter($record->created_at)) {
            throw ValidationException::withMessages([
                'token' => ['Token inválido ou expirado.'],
            ]);
        }

        $user = User::query()->where('email', $data->email)->firstOrFail();
        $user->update(['password' => $data->password]);

        DB::table('password_reset_tokens')
            ->where('email', $data->email)
            ->where('tenant_id', Tenant::current()->getKey())
            ->delete();
    }
}
