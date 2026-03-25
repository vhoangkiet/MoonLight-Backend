<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends Controller
{
    use ApiResponse;

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

            return $this->successResponse($result);
        } catch (DomainException $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                code: $e->getCode(),
            );
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }

            return $this->errorResponse(
                message: 'Unexpected error',
                code: empty($e->getCode()) ? Response::HTTP_BAD_REQUEST : $e->getCode(),
            );
        }
    }
}
