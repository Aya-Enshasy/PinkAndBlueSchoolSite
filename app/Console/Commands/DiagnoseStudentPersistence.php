<?php

namespace App\Console\Commands;

use App\Support\DatabaseConnectionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnoseStudentPersistence extends Command
{
    protected $signature = 'app:diagnose-student-persistence';

    protected $description = 'Diagnose DB target, schema, and student table persistence state.';

    public function handle(): int
    {
        $payload = [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'env' => $this->environmentSnapshot(),
            'config' => $this->databaseConfigSnapshot(),
        ];

        try {
            DatabaseConnectionGuard::ensureConnectionWithRetry();
            $payload['connection'] = $this->runtimeConnectionSnapshot();
            $payload['schema'] = $this->schemaSnapshot();
            $payload['students'] = $this->studentsSnapshot();
            $payload['migrations'] = $this->migrationsSnapshot();

            Log::info('Student persistence diagnosis passed', $payload);
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $payload['error'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
            ];

            Log::error('Student persistence diagnosis failed', $payload);
            $this->error(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function environmentSnapshot(): array
    {
        return [
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'DB_HOST' => env('DB_HOST'),
            'DB_PORT' => env('DB_PORT'),
            'DB_DATABASE' => env('DB_DATABASE'),
            'DB_USERNAME' => env('DB_USERNAME'),
            'DB_SSLMODE' => env('DB_SSLMODE'),
            'DB_ENDPOINT_ID' => env('DB_ENDPOINT_ID'),
            'DATABASE_URL_PRESENT' => env('DATABASE_URL') !== null,
            'DB_URL_PRESENT' => env('DB_URL') !== null,
            'APP_ENV' => env('APP_ENV'),
            'APP_DEBUG' => env('APP_DEBUG'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseConfigSnapshot(): array
    {
        return [
            'default' => config('database.default'),
            'pgsql' => [
                'driver' => config('database.connections.pgsql.driver'),
                'url_present' => ! empty(config('database.connections.pgsql.url')),
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username'),
                'search_path' => config('database.connections.pgsql.search_path'),
                'sslmode' => config('database.connections.pgsql.sslmode'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimeConnectionSnapshot(): array
    {
        $row = (array) DB::selectOne(
            'select current_database() as current_database, current_user as current_user, inet_server_addr()::text as server_addr, inet_server_port() as server_port, version() as version'
        );

        return [
            'connection_name' => DB::connection()->getName(),
            'current_database' => $row['current_database'] ?? null,
            'current_user' => $row['current_user'] ?? null,
            'server_addr' => $row['server_addr'] ?? null,
            'server_port' => $row['server_port'] ?? null,
            'version' => $row['version'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaSnapshot(): array
    {
        return [
            'students_table_exists' => Schema::hasTable('students'),
            'students_columns' => Schema::hasTable('students') ? Schema::getColumnListing('students') : [],
            'storage_logs_writable' => is_writable(storage_path('logs')),
            'storage_app_writable' => is_writable(storage_path('app')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentsSnapshot(): array
    {
        if (! Schema::hasTable('students')) {
            return [
                'count' => null,
                'latest' => null,
            ];
        }

        return [
            'count' => DB::table('students')->count(),
            'latest' => DB::table('students')
                ->select(['id', 'student_id_number', 'academic_year', 'created_at', 'updated_at'])
                ->orderByDesc('id')
                ->first(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function migrationsSnapshot(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [
                'exists' => false,
                'count' => 0,
                'latest' => [],
            ];
        }

        return [
            'exists' => true,
            'count' => DB::table('migrations')->count(),
            'latest' => DB::table('migrations')
                ->select(['id', 'migration', 'batch'])
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ];
    }
}

