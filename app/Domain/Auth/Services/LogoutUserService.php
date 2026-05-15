<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LogoutUserService
{
    public function handle(User $user): void
    {
        try {
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', $user::class)
                ->delete();
        } catch (\Throwable $exception) {
            report($exception);
            throw $exception;
        }
    }
}
