<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateIncrementalStudentBackup implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public int $studentId
    ) {
    }

    public function handle(): void
    {
        try {
            $student = Student::find($this->studentId);

            $payload = [
                'event' => $this->event,
                'student_id' => $this->studentId,
                'timestamp' => now()->toIso8601String(),
                'data' => $student?->toArray(),
            ];

            $directory = 'backups/incremental';
            Storage::disk('local')->makeDirectory($directory);
            Storage::disk('local')->append(
                $directory.'/'.now()->format('Ymd').'.jsonl',
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        } catch (Throwable $exception) {
            try {
                Log::channel('security')->error('Incremental student backup failed', [
                    'event' => $this->event,
                    'student_id' => $this->studentId,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                Log::error('Incremental student backup failed', [
                    'event' => $this->event,
                    'student_id' => $this->studentId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
