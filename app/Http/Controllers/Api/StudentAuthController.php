<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id_number' => ['required', 'string', 'max:80'],
            'academic_year' => ['nullable', 'string', 'max:30'],
        ]);

        $student = Student::query()
            ->where('student_id_number', trim($data['student_id_number']))
            ->when(
                filled($data['academic_year'] ?? null),
                fn ($query) => $query->where('academic_year', trim($data['academic_year']))
            )
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->first();

        if (! $student) {
            return response()->json([
                'message' => 'لم نجد طالبًا بهذه الهوية. تأكد من الرقم أو راجع الإدارة.',
            ], 422);
        }

        $plainToken = Str::random(80);

        $session = StudentSession::query()->create([
            'student_id' => $student->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $request->session()->put('student_session_id', $session->id);

        return response()->json([
            'mode' => 'registered',
            'token' => $plainToken,
            'student' => $this->studentPayload($student),
            'progress' => $this->progressPayload($student),
        ]);
    }

    public function guest(Request $request): JsonResponse
    {
        $request->session()->forget('student_session_id');

        return response()->json([
            'mode' => 'guest',
            'student' => null,
            'message' => 'دخلت كزائر. تستطيع التجربة بدون حفظ التقدم.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $session = $this->activeSession($request);

        if (! $session) {
            return response()->json([
                'mode' => 'guest',
                'student' => null,
                'progress' => [],
                'summary' => $this->emptySummary(),
            ]);
        }

        $session->forceFill(['last_activity_at' => now()])->save();
        $student = $session->student()->firstOrFail();

        return response()->json([
            'mode' => 'registered',
            'student' => $this->studentPayload($student),
            'progress' => $this->progressPayload($student),
            'summary' => $this->summaryPayload($student),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $session = $this->activeSession($request);

        if ($session) {
            $session->forceFill(['revoked_at' => now()])->save();
        }

        $request->session()->forget('student_session_id');

        return response()->json(['status' => 'logged_out']);
    }

    public function activeSession(Request $request): ?StudentSession
    {
        $token = $request->bearerToken() ?: (string) $request->header('X-Student-Token');
        $sessionId = $request->session()->get('student_session_id');

        $query = StudentSession::query()->with('student');

        if ($token !== '') {
            $query->where('token_hash', hash('sha256', $token));
        } elseif ($sessionId) {
            $query->whereKey($sessionId);
        } else {
            return null;
        }

        /** @var StudentSession|null $session */
        $session = $query->first();

        return $session?->isActive() ? $session : null;
    }

    private function studentPayload(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'grade' => $student->grade,
            'gradeNumber' => $student->gradeNumber(),
            'academicYear' => $student->academic_year,
            'studentIdNumber' => $student->student_id_number,
        ];
    }

    private function progressPayload(Student $student): array
    {
        return $student->progress()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($progress) => [
                'lessonKey' => $progress->lesson_key,
                'lessonTitle' => $progress->lesson_title,
                'grade' => $progress->grade,
                'subject' => $progress->subject,
                'unitNo' => $progress->unit_no,
                'xp' => $progress->xp,
                'streak' => $progress->streak,
                'hearts' => $progress->hearts,
                'progressPercent' => $progress->progress_percent,
                'currentBlock' => $progress->current_block,
                'completed' => $progress->completed,
                'sections' => $progress->sections ?: [],
                'activity' => $progress->activity ?: [],
                'completedAt' => optional($progress->completed_at)->toIso8601String(),
                'updatedAt' => optional($progress->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function summaryPayload(Student $student): array
    {
        $summary = $student->progress()
            ->selectRaw('COALESCE(SUM(xp), 0) as xp_total')
            ->selectRaw('COUNT(*) as lessons_started')
            ->selectRaw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as lessons_completed')
            ->first();

        $lastProgress = $student->progress()->latest('updated_at')->first();

        return [
            'xp' => (int) ($summary?->xp_total ?? 0),
            'lessonsStarted' => (int) ($summary?->lessons_started ?? 0),
            'lessonsCompleted' => (int) ($summary?->lessons_completed ?? 0),
            'lastLesson' => $lastProgress?->lesson_title,
            'lastSubject' => $lastProgress?->subject,
            'lastProgressPercent' => (int) ($lastProgress?->progress_percent ?? 0),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'xp' => 0,
            'lessonsStarted' => 0,
            'lessonsCompleted' => 0,
            'lastLesson' => null,
            'lastSubject' => null,
            'lastProgressPercent' => 0,
        ];
    }
}
