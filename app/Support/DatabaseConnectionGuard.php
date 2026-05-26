<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DatabaseConnectionGuard
{
    public static function ensureConnectionWithRetry(?string $connectionName = null): void
    {
        $connectionName ??= config('database.default');
        $attempts = max(1, (int) env('DB_RETRY_ATTEMPTS', 3));
        $sleepMs = max(50, (int) env('DB_RETRY_SLEEP_MS', 300));
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                DB::purge($connectionName);
                DB::connection($connectionName)->getPdo();

                return;
            } catch (Throwable $exception) {
                $lastException = $exception;

                Log::channel('security')->warning('Database connection attempt failed', [
                    'connection' => $connectionName,
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                    'message' => $exception->getMessage(),
                ]);

                if ($attempt < $attempts) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        throw new RuntimeException(
            'Unable to connect to the database after retry attempts.',
            previous: $lastException
        );
    }
}

