<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper for controller to catch errors from service and return proper JSON format.
 */
abstract class BaseController extends Controller
{
    use ApiResponse;

    /**
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
                code: ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : Response::HTTP_BAD_REQUEST,
            );
        } catch (\Throwable $e) {
            if (app()->environment('testing', 'local')) {
                throw $e;
            }

            return $this->errorResponse(
                message: 'Unexpected error',
                code: ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : Response::HTTP_BAD_REQUEST,
            );
        }
    }
}
