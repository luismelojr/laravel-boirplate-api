<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Models\User;

class VerifyEmailService
{
    public function resend(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }

    public function verify(User $user, string $hash): bool
    {
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }
}
