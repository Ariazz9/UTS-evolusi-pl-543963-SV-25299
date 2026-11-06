<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Memeriksa kesehatan sistem (Database dan Cache).
     * Mengembalikan HTTP 200 jika semua sehat, atau HTTP 503 jika ada layanan yang bermasalah.
     */
    public function check(Request $request): JsonResponse
    {
        $isHealthy = true;
        $checks = [];

        // 1. Pengecekan Basis Data (Database)
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            $checks['database'] = [
                'status'  => 'up',
                'message' => 'Database connection established successfully.',
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status'  => 'down',
                'message' => 'Database connection failed.',
                'error'   => $e->getMessage(),
            ];

            // Log terstruktur: Gagal menghubungi basis data (Level: error)
            Log::error('Health check failed: Database connection failure', [
                'who' => [
                    'actor' => 'system_healthcheck',
                    'ip'    => $request->ip(),
                ],
                'what' => [
                    'check'      => 'database',
                    'action'     => 'ping_database',
                    'connection' => config('database.default'),
                    'host'       => config('database.connections.' . config('database.default') . '.host'),
                    'database'   => config('database.connections.' . config('database.default') . '.database'),
                ],
                'reason' => $e->getMessage(),
            ]);
        }

        // 2. Pengecekan Cache
        try {
            $probeKey = 'health_check_probe_' . uniqid();
            Cache::put($probeKey, 'ok', 10);
            $retrieved = Cache::get($probeKey);
            Cache::forget($probeKey);

            if ($retrieved !== 'ok') {
                throw new \RuntimeException('Cache write/read probe returned invalid value.');
            }

            $checks['cache'] = [
                'status'  => 'up',
                'message' => 'Cache store is functioning normally.',
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['cache'] = [
                'status'  => 'down',
                'message' => 'Cache store probe failed.',
                'error'   => $e->getMessage(),
            ];

            // Log terstruktur: Gagal memeriksa cache (Level: error)
            Log::error('Health check failed: Cache store probe failure', [
                'who' => [
                    'actor' => 'system_healthcheck',
                    'ip'    => $request->ip(),
                ],
                'what' => [
                    'check'  => 'cache',
                    'action' => 'cache_probe',
                    'driver' => config('cache.default'),
                ],
                'reason' => $e->getMessage(),
            ]);
        }

        $statusCode = $isHealthy ? 200 : 503;
        $responsePayload = [
            'status'    => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks'    => $checks,
        ];

        return response()->json($responsePayload, $statusCode);
    }
}

