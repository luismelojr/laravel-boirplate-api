<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatusEnum;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'status',
        'avatar_url',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
