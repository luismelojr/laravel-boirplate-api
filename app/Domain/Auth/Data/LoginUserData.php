<?php

declare(strict_types=1);

namespace App\Domain\Auth\Data;

readonly class LoginUserData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }
}
