<?php

namespace App\Jobs;

use App\Services\StudentSpreadsheetSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncStudentSpreadsheet implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public int $studentId
    ) {
    }

    public function handle(StudentSpreadsheetSyncService $spreadsheetSyncService): void
    {
        try {
            $spreadsheetSyncService->syncByEvent($this->event, $this->studentId);
        } catch (Throwable $exception) {
            try {
                Log::channel('security')->error('Student spreadsheet sync failed', [
                    'event' => $this->event,
                    'student_id' => $this->studentId,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                Log::error('Student spreadsheet sync failed', [
                    'event' => $this->event,
                    'student_id' => $this->studentId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}

