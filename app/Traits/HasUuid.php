<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            $column = $model->getUuidColumn();

            if (empty($model->{$column})) {
                $model->{$column} = (string) Str::uuid();
            }
        });
    }

    public function getUuidColumn(): string
    {
        return 'uuid';
    }
}
