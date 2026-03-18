<?php

use App\Exceptions\DomainException;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            $shouldReturnJson = $request->expectsJson() || $request->is('api/*');

            if (! $shouldReturnJson) {
                return null;
            }

            if ($e instanceof DomainException) {
                return ApiResponse::error(
                    message: __($e->translationKey, $e->context),
                    status: $e->status,
                    errors: null,
                    code: $e->domainCode,
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    message: __('api.unauthenticated'),
                    status: Response::HTTP_UNAUTHORIZED,
                    code: 'UNAUTHENTICATED',
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error(
                    message: __('api.forbidden'),
                    status: Response::HTTP_FORBIDDEN,
                    code: 'FORBIDDEN',
                );
            }

            if ($e instanceof UnauthorizedException) {
                return ApiResponse::error(
                    message: __('api.forbidden'),
                    status: Response::HTTP_FORBIDDEN,
                    code: 'FORBIDDEN',
                );
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error(
                    message: __('api.not_found'),
                    status: Response::HTTP_NOT_FOUND,
                    code: 'NOT_FOUND',
                );
            }

            if ($e instanceof TooManyRequestsHttpException) {
                return ApiResponse::error(
                    message: __('api.too_many_requests'),
                    status: Response::HTTP_TOO_MANY_REQUESTS,
                    code: 'TOO_MANY_REQUESTS',
                );
            }

            return ApiResponse::error(
                message: __('api.unexpected_error'),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'UNEXPECTED_ERROR',
            );
        });
    })->create();
