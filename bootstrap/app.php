<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Modules\AuditCompliance\Console\PruneExpiredGdprExports;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        PruneExpiredGdprExports::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'i18n_redirected',
        ]);
    })

    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(PruneExpiredGdprExports::class)
        ->daily()
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
            }

            if ($e instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
                if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }
            }
        });
    })->create();
