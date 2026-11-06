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
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });

        // Log terstruktur: Validasi input gagal (Level: warning)
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::warning('Validation failed on incoming request', [
                'who' => [
                    'user_id' => $request->user()?->id,
                    'ip'      => $request->ip(),
                ],
                'what' => [
                    'action'        => 'validate_request',
                    'endpoint'      => $request->method() . ' ' . $request->path(),
                    'failed_fields' => array_keys($e->errors()),
                ],
                'reason' => $e->validator->errors()->first(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // Log terstruktur: Gagal query / koneksi basis data pada request (Level: error)
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error('Database error encountered during request execution', [
                'who' => [
                    'user_id' => $request->user()?->id,
                    'ip'      => $request->ip(),
                ],
                'what' => [
                    'action'   => 'database_query',
                    'endpoint' => $request->method() . ' ' . $request->path(),
                ],
                'reason' => $e->getMessage(),
            ]);

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Database connection or query error.',
                ], 500);
            }
        });
    })->create();
