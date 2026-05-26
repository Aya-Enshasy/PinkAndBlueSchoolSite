<?php

namespace App\Observers;

use App\Jobs\CreateIncrementalStudentBackup;
use App\Jobs\SyncStudentSpreadsheet;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentObserver
{
    public function created(Student $student): void
    {
        $this->dispatchSafely('created', $student->id);
    }

    public function updated(Student $student): void
    {
        $this->dispatchSafely('updated', $student->id);
    }

    public function deleted(Student $student): void
    {
        $this->dispatchSafely('deleted', $student->id);
    }

    private function dispatchSafely(string $event, int $studentId): void
    {
        try {
            // Run after the HTTP response to keep create/update/delete flows fast and resilient.
            CreateIncrementalStudentBackup::dispatchAfterResponse($event, $studentId);
            SyncStudentSpreadsheet::dispatchAfterResponse($event, $studentId);
        } catch (Throwable $exception) {
            try {
                Log::channel('security')->error('Student observer dispatch failed', [
                    'event' => $event,
                    'student_id' => $studentId,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                Log::error('Student observer dispatch failed', [
                    'event' => $event,
                    'student_id' => $studentId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
