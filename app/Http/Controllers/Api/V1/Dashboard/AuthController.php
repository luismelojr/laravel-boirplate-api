<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Domain\Auth\Data\AcceptInviteData;
use App\Domain\Auth\Data\ForgotPasswordData;
use App\Domain\Auth\Data\LoginUserData;
use App\Domain\Auth\Data\RegisterData;
use App\Domain\Auth\Data\ResetPasswordData;
use App\Domain\Auth\Services\AcceptInviteService;
use App\Domain\Auth\Services\ForgotPasswordService;
use App\Domain\Auth\Services\LoginUserService;
use App\Domain\Auth\Services\LogoutUserService;
use App\Domain\Auth\Services\RegisterTenantService;
use App\Domain\Auth\Services\ResetPasswordService;
use App\Domain\Auth\Services\VerifyEmailService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Dashboard\Auth\AcceptInviteRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Dashboard\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\Dashboard\Tenant\TenantResource;
use App\Http\Resources\Api\V1\Dashboard\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterTenantService $service): JsonResponse
    {
        $data = RegisterData::from($request->validated());
        $result = $service->handle($data);

        return $this->created([
            'tenant' => new TenantResource($result['tenant']),
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Tenant criado com sucesso');
    }

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

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordService $service): JsonResponse
    {
        $data = ForgotPasswordData::from($request->validated());
        $service->handle($data);

        return $this->success([], 'Se este e-mail estiver cadastrado, você receberá um link em breve.');
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordService $service): JsonResponse
    {
        $data = ResetPasswordData::from($request->validated());
        $service->handle($data);

        return $this->success([], 'Senha redefinida com sucesso.');
    }

    public function resendVerification(Request $request, VerifyEmailService $service): JsonResponse
    {
        $service->resend($request->user());

        return $this->success([], 'E-mail de verificação reenviado.');
    }

    public function acceptInvite(AcceptInviteRequest $request, AcceptInviteService $service): JsonResponse
    {
        $data = AcceptInviteData::from($request->validated());
        $result = $service->handle($data);

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Conta ativada com sucesso.');
    }

    public function verifyEmail(Request $request, string $id, string $hash, VerifyEmailService $service): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Link de verificação inválido ou expirado.', 403);
        }

        $user = User::withoutGlobalScopes()->where('uuid', $id)->firstOrFail();

        if (! $service->verify($user, $hash)) {
            return $this->error('Link de verificação inválido.', 403);
        }

        return $this->success([], 'E-mail verificado com sucesso.');
    }
}
