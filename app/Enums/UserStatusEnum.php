<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
