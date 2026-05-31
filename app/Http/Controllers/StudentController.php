<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Services\SecureUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class StudentController extends Controller
{
    private const IMAGE_FIELDS = [
        'student_id_image',
        'father_id_image',
        'birth_certificate_image',
    ];

    public function __construct(private readonly SecureUploadService $secureUploadService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $grade = (string) $request->query('grade', '');
        $academicYear = (string) $request->query('academic_year', '');
        $highlightId = (int) $request->query('highlight', 0);

        $students = Student::query()
            ->with('latestProgress')
            ->withSum('progress as learning_xp', 'xp')
            ->withCount([
                'progress as started_lessons_count',
                'progress as completed_lessons_count' => fn ($query) => $query->where('completed', true),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('student_id_number', 'like', "%{$search}%")
                        ->orWhere('father_id_number', 'like', "%{$search}%");
                });
            })
            ->when($grade !== '', fn ($query) => $query->where('grade', $grade))
            ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $academicYears = Student::query()
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return view('students.index', [
            'students' => $students,
            'search' => $search,
            'grade' => $grade,
            'academicYear' => $academicYear,
            'highlightId' => $highlightId,
            'grades' => Student::GRADE_OPTIONS,
            'academicYears' => $academicYears,
        ]);
    }

    public function create(): View
    {
        return view('students.create', [
            'grades' => Student::GRADE_OPTIONS,
            'defaultAcademicYear' => now()->year.'/'.(now()->year + 1),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
{
    $data = $request->validated();

    $uploadedUrls = [];

    try {

        \Log::info('Starting student store process', [
            'data' => $data,
            'ip' => $request->ip(),
        ]);

        foreach (self::IMAGE_FIELDS as $field) {

            if (! $request->hasFile($field)) {
                $data[$field] = null;
                continue;
            }

            \Log::info("Uploading image: {$field}");

            $uploadedUrls[$field] = $this->secureUploadService
                ->storeImage(
                    $request->file($field),
                    $field,
                    'students'
                );

            $data[$field] = $uploadedUrls[$field];

            \Log::info("Image uploaded: {$field}", [
                'url' => $uploadedUrls[$field]
            ]);
        }

        \Log::info('Creating student in DB');

        $student = Student::create($data);

        \Log::info('Student created successfully', [
            'student_id' => $student->id
        ]);

        $this->writeStudentBackup($student, 'created');

        return redirect('/students')
            ->with('success', 'Student added successfully.');

    } catch (\Throwable $exception) {

        \Log::error('Student store failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        foreach ($uploadedUrls as $uploadedUrl) {
            $this->secureUploadService->deleteImage($uploadedUrl);
        }

        return back()
            ->withInput()
            ->with('error', 'Failed to add student.');
    }
}
    
    public function show(string $student): View|RedirectResponse
    {
        $student = Student::query()
            ->with('progress')
            ->withSum('progress as learning_xp', 'xp')
            ->withCount([
                'progress as started_lessons_count',
                'progress as completed_lessons_count' => fn ($query) => $query->where('completed', true),
            ])
            ->find($student);

        if (! $student) {
            return redirect()->route('students.index')->with('error', 'Student not found.');
        }

        $progressRows = $student->progress()
            ->latest('updated_at')
            ->get();

        return view('students.show', compact('student', 'progressRows'));
    }

    public function edit(string $student): View|RedirectResponse
    {
        $student = Student::query()->find($student);

        if (! $student) {
            return redirect()->route('students.index')->with('error', 'Student not found.');
        }

        return view('students.edit', [
            'student' => $student,
            'grades' => Student::GRADE_OPTIONS,
            'defaultAcademicYear' => $student->academic_year,
        ]);
    }

    public function update(UpdateStudentRequest $request, string $id): RedirectResponse
{
    $student = Student::find($id);

    if (! $student) {
        return redirect()
            ->route('students.index')
            ->with('error', 'Student not found.');
    }

    $data = $request->validated();

    $newUploadedUrls = [];
    $oldImages = [];

    try {

        \Log::info('Starting student update process', [
            'student_id' => $student->id,
            'data' => $data,
            'ip' => $request->ip(),
        ]);

        foreach (self::IMAGE_FIELDS as $field) {

            if ($request->hasFile($field)) {

                // خزّن القديم عشان نحذفه بعد النجاح
                $oldImages[$field] = $student->{$field};

                // ارفع الجديد وخزّنه
                $data[$field] = $this->secureUploadService->storeImage(
                    $request->file($field),
                    $field,
                    'students'
                );

                \Log::info("Image updated: {$field}", [
                    'new_url' => $data[$field]
                ]);
            }
        }

        \Log::info('Updating student in DB');

        $student->update($data);

        $student->refresh();

        \Log::info('Student updated successfully', [
            'student_id' => $student->id
        ]);

        // حذف الصور القديمة بعد نجاح التحديث
        foreach ($oldImages as $field => $oldImage) {
            if ($oldImage) {
                \Log::info("Deleting old image: {$field}");
                $this->secureUploadService->deleteImage($oldImage);
            }
        }

        try {
            $this->writeStudentBackup($student, 'updated');
        } catch (\Throwable $backupException) {
            \Log::warning('Backup failed', [
                'message' => $backupException->getMessage()
            ]);
        }

        return redirect('/students')
            ->with('success', 'Student updated successfully.');

    } catch (\Throwable $exception) {

        \Log::error('Student update failed', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // حذف الصور الجديدة لو فشل التحديث
        foreach ($newUploadedUrls as $uploadedUrl) {
            $this->secureUploadService->deleteImage($uploadedUrl);
        }

        return back()
            ->withInput()
            ->with('error', 'Failed to update student.');
    }
}
    public function destroy(string $student): RedirectResponse
    {
        $student = Student::query()->find($student);

        if (! $student) {
            return redirect()->route('students.index')->with('error', 'Student not found.');
        }

        foreach (self::IMAGE_FIELDS as $field) {
            $this->secureUploadService->deleteImage($student->{$field});
        }

        $this->writeStudentBackup($student, 'deleted');

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Student deleted successfully.');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logSecurityError(string $message, array $context = []): void
    {
        try {
            Log::channel('security')->error($message, $context);
        } catch (Throwable) {
            Log::error($message, $context);
        }
    }

    private function writeStudentBackup(Student $student, string $event): void
{
    try {

        $path = storage_path('app/student_backups.json');

        $payload = [
            'event' => $event,
            'student_id' => $student->id,
            'timestamp' => now()->toIso8601String(),
            'data' => $student->toArray(),
        ];

        file_put_contents(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

    } catch (\Throwable $e) {

        \Log::error('Backup write failed', [
            'message' => $e->getMessage()
        ]);
    }
}
}
