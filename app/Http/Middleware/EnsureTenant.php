<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Finders\HeaderTenantFinder;
use App\Support\Api\ApiResponseFactory;
use Closure;
use Illuminate\Http\Request;

class EnsureTenant
{
    public function __construct(private readonly HeaderTenantFinder $finder) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->finder->findForRequest($request);

        if (! $tenant) {
            return ApiResponseFactory::error('Tenant não encontrado ou inativo', 422);
        }

        $tenant->makeCurrent();

        return $next($request);
    }
}
