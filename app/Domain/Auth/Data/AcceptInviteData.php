<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class AcceptInviteData extends Data
{
    public function __construct(
        public readonly string $token,
        #[Hidden]
        public readonly string $password,
    ) {}
}
