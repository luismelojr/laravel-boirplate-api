<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Dashboard\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
