<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code, options: JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Recurso criado com sucesso',
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED, $meta);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT, options: JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    protected function error(
        string $message = 'Erro',
        int $code = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code, options: JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function notFound(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    protected function unauthorized(string $message = 'Não autorizado'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    protected function forbidden(string $message = 'Sem permissão'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    protected function validationError(array $errors, string $message = 'Erro de validação'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $code = Response::HTTP_OK,
        array $extraMeta = []
    ): JsonResponse {
        $meta = array_merge([
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], $extraMeta);

        return $this->success(
            data: $paginator->items(),
            message: $message,
            code: $code,
            meta: $meta
        );
    }
}
