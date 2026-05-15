<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domain\Auth\Data\LoginUserData;
use App\Domain\Auth\Services\LoginUserService;
use App\Domain\Auth\Services\LogoutUserService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Dashboard\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class AuthController extends ApiController
{
    public function login(LoginRequest $request, LoginUserService $service): JsonResponse
    {
        $dto = LoginUserData::from($request->validated());
        $result = $service->handle($dto);

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login realizado com sucesso');
    }

    public function logout(Request $request, LogoutUserService $service): JsonResponse
    {
        $service->handle($request->user());

        return $this->success(data: [], message: 'Logout realizado com sucesso');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            data: new UserResource($request->user()),
            message: 'Usuário autenticado'
        );
    }
}
