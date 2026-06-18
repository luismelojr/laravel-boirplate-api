<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard\Admin;

use App\Domain\Auth\Data\InviteUserData;
use App\Domain\Auth\Services\InviteUserService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Auth\InviteUserRequest;
use App\Http\Resources\Api\V1\Dashboard\User\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin
 */
class AdminUserController extends ApiController
{
    public function invite(InviteUserRequest $request, InviteUserService $service): JsonResponse
    {
        $data = InviteUserData::from($request->validated());
        $user = $service->handle($data);

        return $this->created(
            ['user' => new UserResource($user)],
            'Convite enviado com sucesso.'
        );
    }
}
