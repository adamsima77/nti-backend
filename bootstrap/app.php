<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $exceptions->render(function (Throwable $e, $request) {
            // Handle authentication exceptions for API routes
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
            }
            
            // Handle route not found during redirect attempts (API should return 401)
            if ($e instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
                if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
            }
        });
    })->create();
