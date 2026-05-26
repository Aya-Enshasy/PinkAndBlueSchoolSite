<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StudentSpreadsheetSyncService
{
    public function syncByEvent(string $event, int $studentId): void
    {
        if (! config('student_sheet_sync.enabled')) {
            return;
        }

        $endpoint = trim((string) config('student_sheet_sync.webhook_url'));
        if ($endpoint === '') {
            return;
        }

        $payload = [
            'event' => $event,
            'student_id' => $studentId,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($event !== 'deleted') {
            $student = Student::query()->find($studentId);
            if (! $student) {
                return;
            }

            $payload['student'] = [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'grade' => $student->grade,
                'academic_year' => $student->academic_year,
                'student_id_number' => $student->student_id_number,
                'father_id_number' => $student->father_id_number,
                'mobile_number' => $student->mobile_number,
                'alternative_mobile_number' => $student->alternative_mobile_number,
                'birth_date' => optional($student->birth_date)->format('Y-m-d'),
                'created_at' => optional($student->created_at)->toIso8601String(),
                'updated_at' => optional($student->updated_at)->toIso8601String(),
            ];
        }

        $token = trim((string) config('student_sheet_sync.token'));
        if ($token !== '') {
            $payload['token'] = $token;
        }

        $response = Http::acceptJson()
            ->timeout((int) config('student_sheet_sync.timeout_seconds', 10))
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Student sheet sync failed with status '.$response->status());
        }
    }
}
