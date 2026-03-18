<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $shouldReturnJson = $request->expectsJson() || $request->is('api/*');

            if (! $shouldReturnJson) {
                return null;
            }

            if ($e instanceof \App\Exceptions\DomainException) {
                return \App\Support\Api\ApiResponse::error(
                    message: __($e->translationKey, $e->context),
                    status: $e->status,
                    errors: null,
                    code: $e->code,
                );
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return \App\Support\Api\ApiResponse::error(
                    message: __('api.unauthenticated'),
                    status: Response::HTTP_UNAUTHORIZED,
                    code: 'UNAUTHENTICATED',
                );
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return \App\Support\Api\ApiResponse::error(
                    message: __('api.forbidden'),
                    status: Response::HTTP_FORBIDDEN,
                    code: 'FORBIDDEN',
                );
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return \App\Support\Api\ApiResponse::error(
                    message: __('api.not_found'),
                    status: Response::HTTP_NOT_FOUND,
                    code: 'NOT_FOUND',
                );
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                return \App\Support\Api\ApiResponse::error(
                    message: __('api.too_many_requests'),
                    status: Response::HTTP_TOO_MANY_REQUESTS,
                    code: 'TOO_MANY_REQUESTS',
                );
            }

            return \App\Support\Api\ApiResponse::error(
                message: __('api.unexpected_error'),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'UNEXPECTED_ERROR',
            );
        });
    })->create();
