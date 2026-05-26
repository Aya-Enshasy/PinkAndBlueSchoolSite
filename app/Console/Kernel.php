<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('backup:secure-run')
            ->dailyAt(env('BACKUP_SCHEDULE_TIME', '02:00'))
            ->withoutOverlapping()
            ->onOneServer();
    }
}

