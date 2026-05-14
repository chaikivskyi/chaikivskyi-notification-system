<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

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
        $exceptions
            ->render(function (ValidationException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'errors' => $e->errors(),
                        'message' => $e->getMessage(),
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            })
            ->render(function (AuthenticationException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Unauthenticated',
                    ], Response::HTTP_UNAUTHORIZED);
                }
            })
            ->render(function (NotFoundHttpException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Resource not found',
                    ], Response::HTTP_NOT_FOUND);
                }
            })
            ->render(function (RouteNotFoundException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Route not found',
                    ], Response::HTTP_NOT_FOUND);
                }
            })
            ->render(function (ThrottleRequestsException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Too Many Attempts',
                    ], Response::HTTP_TOO_MANY_REQUESTS);
                }
            })
            ->render(function (AccessDeniedHttpException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $e->getMessage(),
                    ], Response::HTTP_FORBIDDEN);
                }
            })
            ->render(function (MethodNotAllowedHttpException $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $e->getMessage(),
                    ], Response::HTTP_METHOD_NOT_ALLOWED);
                }
            })
            ->render(function (Throwable $e, Request $request) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Internal Server Error',
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            });
    })->create();
