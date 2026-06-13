<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Modules\AuditCompliance\Console\PruneExpiredGdprExports;
use Modules\AuditCompliance\Models\SystemEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            return route('login');
        });
    })

    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(PruneExpiredGdprExports::class)
            ->everyFiveMinutes()
            ->withoutOverlapping();
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

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

            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            $isServerError   = $statusCode >= 500;
            $isSecurityAlert = $statusCode === 403 || $statusCode === 429;

            if ($isServerError || $isSecurityAlert) {
                $rawUserId = auth()->id();
                $userId = is_numeric($rawUserId) ? (int) $rawUserId : null;

                $message = $e->getMessage() ?: get_class($e);

                SystemEvent::create([
                    'event_type'  => $isSecurityAlert ? 'SECURITY_ALERT' : 'SYSTEM_ERROR',
                    'severity'    => $isSecurityAlert ? 'WARNING' : 'CRITICAL',
                    'message'     => $message,
                    'stack_trace' => $e->getTraceAsString(),
                    'context'     => json_encode([
                        'url'            => request()->fullUrl(),
                        'method'         => request()->method(),
                        'input'          => request()->except(['password', 'password_confirmation', 'token']),
                        'module_context' => 'Global Exception Handler intercept',
                    ]),
                    'user_id'    => $userId,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'created_at' => now(),
                ]);
            }

            return null;
        });

    })->create();
