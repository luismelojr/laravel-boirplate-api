<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(
        public readonly string $tenant_name,
        public readonly string $tenant_slug,
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
    ) {}
}
