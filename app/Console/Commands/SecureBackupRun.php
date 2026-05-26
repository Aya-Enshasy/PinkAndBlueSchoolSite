<?php

namespace App\Console\Commands;

use App\Support\DatabaseConnectionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Backup\BackupDestination\BackupDestination;
use ZipArchive;

class SecureBackupRun extends Command
{
    protected $signature = 'backup:secure-run {--with-files : Include files with the database backup}';

    protected $description = 'Run secure backup, verify archive, and cleanup old backups.';

    public function handle(): int
    {
        try {
            DatabaseConnectionGuard::ensureConnectionWithRetry();

            $runOptions = $this->option('with-files') ? [] : ['--only-db' => true];
            $this->call('backup:run', $runOptions);
            $this->call('backup:clean');

            $verification = $this->verifyLatestBackup();

            Log::channel('security')->info('Secure backup run completed', $verification);
            $this->info('Secure backup completed and verified successfully.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::channel('security')->error('Secure backup run failed', [
                'message' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyLatestBackup(): array
    {
        $backupName = (string) config('backup.backup.name');
        $backupDisks = array_values(array_filter((array) config('backup.backup.destination.disks', [])));

        foreach ($backupDisks as $diskName) {
            $destination = BackupDestination::create($diskName, $backupName);

            if (! $destination->isReachable()) {
                continue;
            }

            $latestBackup = $destination->fresh()->newestBackup();

            if (! $latestBackup || ! $latestBackup->exists() || $latestBackup->sizeInBytes() <= 0) {
                continue;
            }

            $this->assertBackupArchiveIsValid($latestBackup->path(), $destination->diskName(), $destination->disk());

            return [
                'disk' => $destination->diskName(),
                'path' => $latestBackup->path(),
                'size_bytes' => $latestBackup->sizeInBytes(),
            ];
        }

        throw new RuntimeException('Backup verification failed: no valid backup archive was found.');
    }

    private function assertBackupArchiveIsValid(string $backupPath, string $diskName, mixed $disk): void
    {
        $temporaryDirectory = storage_path('app/backup-temp');

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0755, true);
        }

        $localPath = $temporaryDirectory.'/verify_'.Str::uuid()->toString().'.zip';

        $readStream = $disk->readStream($backupPath);

        if (! is_resource($readStream)) {
            throw new RuntimeException("Backup verification failed: could not read backup stream from disk [{$diskName}].");
        }

        $writeStream = fopen($localPath, 'wb');
        stream_copy_to_stream($readStream, $writeStream);
        fclose($readStream);
        fclose($writeStream);

        $zip = new ZipArchive;
        $openResult = $zip->open($localPath);

        if ($openResult !== true) {
            @unlink($localPath);
            throw new RuntimeException("Backup verification failed: invalid zip archive on disk [{$diskName}].");
        }

        if ($zip->numFiles < 1) {
            $zip->close();
            @unlink($localPath);
            throw new RuntimeException("Backup verification failed: archive is empty on disk [{$diskName}].");
        }

        $zip->close();
        @unlink($localPath);
    }
}

