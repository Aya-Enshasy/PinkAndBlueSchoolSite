<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function store(Request $request, StudentAuthController $auth): JsonResponse
    {
        $session = $auth->activeSession($request);

        if (! $session) {
            return response()->json([
                'status' => 'guest',
                'message' => 'التقدم غير محفوظ لأن الطالب دخل كزائر.',
            ]);
        }

        $data = $request->validate([
            'lesson_key' => ['required', 'string', 'max:120'],
            'lesson_title' => ['nullable', 'string', 'max:255'],
            'grade' => ['required', 'integer', 'min:1', 'max:9'],
            'subject' => ['required', 'string', 'max:40'],
            'unit_no' => ['nullable', 'integer', 'min:1', 'max:9'],
            'xp' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'streak' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'hearts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'current_block' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'completed' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array'],
            'activity' => ['nullable', 'array'],
        ]);

        $existing = StudentProgress::query()
            ->where('student_id', $session->student_id)
            ->where('lesson_key', $data['lesson_key'])
            ->first();

        $completed = (bool) ($data['completed'] ?? false) || (bool) ($existing?->completed);

        $progress = StudentProgress::query()->updateOrCreate(
            [
                'student_id' => $session->student_id,
                'lesson_key' => $data['lesson_key'],
            ],
            [
                'lesson_title' => $data['lesson_title'] ?? null,
                'grade' => $data['grade'],
                'subject' => $data['subject'],
                'unit_no' => $data['unit_no'] ?? null,
                'xp' => $data['xp'] ?? 0,
                'streak' => $data['streak'] ?? 1,
                'hearts' => $data['hearts'] ?? 3,
                'progress_percent' => max((int) ($existing?->progress_percent ?? 0), (int) ($data['progress_percent'] ?? 0)),
                'current_block' => $data['current_block'] ?? 0,
                'completed' => $completed,
                'sections' => $data['sections'] ?? [],
                'activity' => $data['activity'] ?? [],
                'completed_at' => $completed ? ($existing?->completed_at ?: now()) : null,
            ]
        );

        $session->forceFill(['last_activity_at' => now()])->save();

        return response()->json([
            'status' => 'saved',
            'progress' => [
                'lessonKey' => $progress->lesson_key,
                'completed' => $progress->completed,
                'progressPercent' => $progress->progress_percent,
                'updatedAt' => optional($progress->updated_at)->toIso8601String(),
            ],
        ]);
    }
}
