<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatusEnum;
use App\Traits\HasUuid;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatusEnum::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
