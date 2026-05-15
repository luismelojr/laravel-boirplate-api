<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionRegister
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Erro de validação',
                status: 422,
                errors: $e->errors()
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Não autorizado', 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Sem permissão', 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Sem permissão', 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Recurso não encontrado', 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Recurso não encontrado', 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Método não permitido', 405);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            return ApiResponseFactory::error('Muitas requisições', 429);
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            $sqlState = $e->errorInfo[0] ?? null;

            if ($sqlState === '22P02') {
                return ApiResponseFactory::error('UUID inválido', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return ApiResponseFactory::error(
                message: 'Erro no banco de dados',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                meta: (bool) config('app.debug') ? ['exception' => class_basename($e)] : []
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! self::shouldHandle($request)) {
                return null;
            }

            $debug = (bool) config('app.debug');

            return ApiResponseFactory::error(
                message: $debug ? $e->getMessage() : 'Erro interno do servidor',
                status: 500,
                meta: $debug ? ['exception' => class_basename($e)] : []
            );
        });
    }

    private static function shouldHandle(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}
