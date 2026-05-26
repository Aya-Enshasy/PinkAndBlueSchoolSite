<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CreateDatabaseBackup extends Command
{
    protected $signature = 'backup:database {--only-db : Dump database only}';

    protected $description = 'Create encrypted database backup (and optional students JSON snapshot).';

    public function handle(): int
    {
        $driver = config('database.default');
        $connection = config("database.connections.$driver");
        $timestamp = now()->format('Ymd_His');

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $dumpPath = $backupDir . DIRECTORY_SEPARATOR . "db_{$driver}_{$timestamp}.sql";
        $encryptedPath = $dumpPath . '.enc';

        $this->createSqlDump($driver, $connection, $dumpPath);

        if (! $this->option('only-db')) {
            $studentsSnapshot = $backupDir . DIRECTORY_SEPARATOR . "students_{$timestamp}.json";
            $students = \App\Models\Student::query()->orderBy('id')->get();
            file_put_contents($studentsSnapshot, $students->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->encryptFile($studentsSnapshot);
            @unlink($studentsSnapshot);
        }

        $this->encryptFile($dumpPath);
        @unlink($dumpPath);

        $this->info("Backup created: {$encryptedPath}");

        return self::SUCCESS;
    }

    private function createSqlDump(string $driver, array $connection, string $dumpPath): void
    {
        if ($driver === 'mysql') {
            $host = escapeshellarg((string) ($connection['host'] ?? '127.0.0.1'));
            $port = escapeshellarg((string) ($connection['port'] ?? '3306'));
            $database = escapeshellarg((string) ($connection['database'] ?? ''));
            $username = escapeshellarg((string) ($connection['username'] ?? ''));
            $password = (string) ($connection['password'] ?? '');
            $passwordArg = $password !== '' ? '-p' . escapeshellarg($password) : '';
            $out = escapeshellarg($dumpPath);

            $cmd = "mysqldump --single-transaction --quick --lock-tables=false -h {$host} -P {$port} -u {$username} {$passwordArg} {$database} > {$out}";
            exec($cmd, $output, $code);

            if ($code !== 0) {
                throw new RuntimeException('mysqldump failed. Ensure mysqldump is installed and credentials are correct.');
            }

            return;
        }

        if ($driver === 'pgsql') {
            $host = (string) ($connection['host'] ?? '127.0.0.1');
            $port = (string) ($connection['port'] ?? '5432');
            $database = (string) ($connection['database'] ?? '');
            $username = (string) ($connection['username'] ?? '');
            $password = (string) ($connection['password'] ?? '');

            putenv("PGPASSWORD={$password}");
            $cmd = sprintf(
                'pg_dump -h %s -p %s -U %s -d %s -f %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($dumpPath)
            );

            exec($cmd, $output, $code);
            putenv('PGPASSWORD');

            if ($code !== 0) {
                throw new RuntimeException('pg_dump failed. Ensure pg_dump is installed and credentials are correct.');
            }

            return;
        }

        throw new RuntimeException("Unsupported database driver for backup: {$driver}");
    }

    private function encryptFile(string $path): void
    {
        $key = (string) env('BACKUP_ENCRYPTION_KEY', '');
        if ($key === '') {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY is missing in environment variables.');
        }

        $contents = file_get_contents($path);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($contents ?: '', 'AES-256-CBC', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed for backup file.');
        }

        file_put_contents($path . '.enc', base64_encode($iv . $encrypted));
    }
}
