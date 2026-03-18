<?php

namespace App\Support\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonResource|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function ok(array|Arrayable|JsonResource|null $data = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => self::normalizeData($data),
            'meta' => (object) $meta,
        ], Response::HTTP_OK);
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonResource  $data
     * @param  array<string, mixed>  $meta
     */
    public static function created(array|Arrayable|JsonResource $data, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => self::normalizeData($data),
            'meta' => (object) $meta,
        ], Response::HTTP_CREATED);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        int $status,
        ?array $errors = null,
        ?string $code = null,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'code' => $code,
            'errors' => $errors ?? (object) [],
            'meta' => (object) $meta,
        ];

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function validationError(array $errors, array $meta = []): JsonResponse
    {
        return self::error(
            message: __('api.validation_failed'),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors,
            code: 'VALIDATION_FAILED',
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonResource|null  $data
     * @return array<string, mixed>|null
     */
    private static function normalizeData(array|Arrayable|JsonResource|null $data): ?array
    {
        if ($data === null) {
            return null;
        }

        if ($data instanceof JsonResource) {
            /** @var array<string, mixed> $resolved */
            $resolved = $data->resolve();

            return $resolved;
        }

        if ($data instanceof Arrayable) {
            /** @var array<string, mixed> $array */
            $array = $data->toArray();

            return $array;
        }

        return $data;
    }
}
