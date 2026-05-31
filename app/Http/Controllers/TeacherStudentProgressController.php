<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TeacherStudentProgressController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $grade = (string) $request->query('grade', '');
        $subject = (string) $request->query('subject', '');

        $students = Student::query()
            ->with('latestProgress')
            ->withSum('progress as learning_xp', 'xp')
            ->withCount([
                'progress as started_lessons_count',
                'progress as completed_lessons_count' => fn ($query) => $query->where('completed', true),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_id_number', 'like', "%{$search}%");
                });
            })
            ->when($grade !== '', fn ($query) => $query->where('grade', $grade))
            ->when($subject !== '', fn ($query) => $query->whereHas('progress', fn ($progress) => $progress->where('subject', $subject)))
            ->orderBy('grade')
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'activeStudents' => StudentProgress::query()->distinct('student_id')->count('student_id'),
            'completedLessons' => StudentProgress::query()->where('completed', true)->count(),
            'totalXp' => StudentProgress::query()->sum('xp'),
        ];

        return view('teacher.student-progress', [
            'students' => $students,
            'grades' => Student::GRADE_OPTIONS,
            'search' => $search,
            'grade' => $grade,
            'subject' => $subject,
            'summary' => $summary,
        ]);
    }
}
