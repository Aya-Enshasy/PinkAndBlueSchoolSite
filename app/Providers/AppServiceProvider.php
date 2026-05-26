<?php

namespace App\Providers;

use App\Models\Student;
use App\Observers\StudentObserver;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app()->setLocale('ar');
        Carbon::setLocale('ar');

        if ((bool) config('student_sheet_sync.enabled', false)) {
            Student::observe(StudentObserver::class);
        }

        $this->configureRateLimiting();
        $this->registerBackupEventLogging();

        if ($this->app->environment('production')) {
            config(['app.debug' => false]);
        }

        if ($this->app->environment('production') && env('APP_FORCE_HTTPS', true)) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('sensitive', function (Request $request) {
            return [
                Limit::perMinute((int) env('SENSITIVE_RATE_LIMIT_PER_MINUTE', 30))->by($request->ip()),
            ];
        });
    }

    private function registerBackupEventLogging(): void
    {
        Event::listen(BackupWasSuccessful::class, function (BackupWasSuccessful $event): void {
            Log::channel('security')->info('Backup succeeded', [
                'disk' => $event->backupDestination->diskName(),
                'backup_name' => $event->backupDestination->backupName(),
                'newest_backup' => $event->backupDestination->newestBackup()?->path(),
            ]);
        });

        Event::listen(CleanupWasSuccessful::class, function (CleanupWasSuccessful $event): void {
            Log::channel('security')->info('Backup cleanup succeeded', [
                'disk' => $event->backupDestination->diskName(),
                'backup_name' => $event->backupDestination->backupName(),
            ]);
        });

        Event::listen(BackupHasFailed::class, function (BackupHasFailed $event): void {
            Log::channel('security')->error('Backup failed', [
                'message' => $event->exception->getMessage(),
            ]);
        });

        Event::listen(CleanupHasFailed::class, function (CleanupHasFailed $event): void {
            Log::channel('security')->error('Backup cleanup failed', [
                'message' => $event->exception->getMessage(),
            ]);
        });
    }
}
