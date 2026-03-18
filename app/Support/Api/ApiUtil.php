<?php

namespace App\Support\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lớp tiện ích trả response API thống nhất.
 *
 * Lưu ý: nội bộ delegate sang ApiResponse để dễ đổi tên về sau.
 */
final class ApiUtil
{
    private function __construct()
    {
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonResource|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function ok(array|Arrayable|JsonResource|null $data = null, array $meta = []): JsonResponse
    {
        return ApiResponse::ok($data, $meta);
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|JsonResource  $data
     * @param  array<string, mixed>  $meta
     */
    public static function created(array|Arrayable|JsonResource $data, array $meta = []): JsonResponse
    {
        return ApiResponse::created($data, $meta);
    }

    public static function noContent(): JsonResponse
    {
        return ApiResponse::noContent();
    }
}

