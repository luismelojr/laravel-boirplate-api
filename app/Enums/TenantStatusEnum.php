<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
