<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends Controller
{
    /**
     * Wrapper để controller bắt lỗi từ service và trả JSON đúng format.
     *
     * @param  callable(): JsonResponse|array<string, mixed>|null  $callback
     */
    protected function execute(callable $callback): JsonResponse
    {
        try {
            $result = $callback();

            if ($result instanceof JsonResponse) {
                return $result;
            }

            return ApiResponse::ok($result);
        } catch (DomainException $e) {
            return ApiResponse::error(
                message: __($e->translationKey, $e->context),
                status: $e->status,
                code: $e->domainCode,
            );
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }

            return ApiResponse::error(
                message: __('api.unexpected_error'),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'UNEXPECTED_ERROR',
            );
        }
    }
}
