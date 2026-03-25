<?php

use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * Validation exception
         */
        $exceptions->render(function (ValidationException $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        /**
         * 404 exception
         */
        $exceptions->render(function (NotFoundHttpException $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
            ], 404);
        });

        /**
         * Authentication exception
         */
        $exceptions->render(function (AuthenticationException $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        });

        /**
         * Domain exception
         */
        $exceptions->render(function (DomainException $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], $e->getCode() ?: 400);
        });

        /**
         * Rate limit exception
         */
        $exceptions->render(function (ThrottleRequestsException $e, $request) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        });

    })
    ->create();
