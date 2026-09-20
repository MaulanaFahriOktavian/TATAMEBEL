<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Check the operational health of the application and its core dependencies.
     */
    public function __invoke(): JsonResponse
    {
        $databaseStatus = 'disconnected';
        $isHealthy = true;

        try {
            DB::connection()->getPdo();
            $databaseStatus = 'connected';
        } catch (Throwable $e) {
            Log::error('Health check probe failed to connect to database', [
                'exception' => $e->getMessage(),
            ]);
            $isHealthy = false;
        }

        return response()->json([
            'success' => true,
            'message' => $isHealthy ? 'TATAMEBEL API is healthy.' : 'TATAMEBEL API is running with degraded dependencies.',
            'data' => [
                'status' => $isHealthy ? 'healthy' : 'degraded',
                'environment' => (string) app()->environment(),
                'timestamp' => now()->toIso8601String(),
                'database' => $databaseStatus,
                'version' => (string) config('app.version', '1.0.0'),
            ],
        ], 200);
    }
}
