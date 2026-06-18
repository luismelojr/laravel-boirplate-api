<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenant = Tenant::current();

            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model): void {
            if (! Tenant::current()) {
                Log::warning('BelongsToTenant: creating model without current tenant — tenant_id will be null.', [
                    'model' => get_class($model),
                ]);

                return;
            }

            if (empty($model->tenant_id)) {
                $model->tenant_id = Tenant::current()->getKey();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
