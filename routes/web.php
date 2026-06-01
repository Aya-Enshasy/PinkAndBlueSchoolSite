<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\LearningUnitController;
use App\Http\Controllers\Api\StudentAuthController;
use App\Http\Controllers\Api\StudentProgressController;
use App\Http\Controllers\Api\TextToSpeechController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlannerItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherStudentProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.portal');
})->name('role.portal');

Route::get('/student', function () {
    return view('welcome', ['initialView' => 'student']);
})->name('student.platform');

Route::middleware(['auth', 'teacher'])->group(function () {
    Route::get('/teacher', function () {
        return view('teacher.builder');
    })->name('teacher.platform');

    Route::get('/teacher/progress', TeacherStudentProgressController::class)->name('teacher.progress');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('students', StudentController::class)->except(['store', 'update', 'destroy']);

    Route::middleware('throttle:sensitive')->group(function () {
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        Route::post('/planner-items', [PlannerItemController::class, 'store'])->name('planner-items.store');
        Route::put('/planner-items/{plannerItem}', [PlannerItemController::class, 'update'])->name('planner-items.update');
        Route::delete('/planner-items/{plannerItem}', [PlannerItemController::class, 'destroy'])->name('planner-items.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::middleware('throttle:sensitive')->group(function () {
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

Route::post('/api/save-progress', function (Request $request) {
    $payload = $request->validate([
        'currentNode' => 'nullable|integer',
        'unlocked' => 'nullable|array',
        'xp' => 'nullable|integer',
        'stars' => 'nullable|array',
    ]);

    session(['game_state' => $payload]);

    return response()->json(['status' => 'saved']);
});

Route::prefix('/api/student')->middleware('throttle:sensitive')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login'])->name('api.student.login');
    Route::post('/guest', [StudentAuthController::class, 'guest'])->name('api.student.guest');
    Route::get('/me', [StudentAuthController::class, 'me'])->name('api.student.me');
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('api.student.logout');
    Route::post('/progress', [StudentProgressController::class, 'store'])->name('api.student.progress');
});

Route::get('/api/images', function (Request $request) {
    $query = trim((string) $request->query('q', 'education'));
    $type = in_array($request->query('type'), ['vector', 'illustration', 'photo'], true)
        ? $request->query('type')
        : 'vector';
    $key = env('PIXABAY_KEY');

    if (!$key) {
        return response()->json([
            'hits' => [],
            'message' => 'PIXABAY_KEY is not configured.',
        ]);
    }

    $response = Http::timeout(8)->get('https://pixabay.com/api/', [
        'key' => $key,
        'q' => trim($query.' cartoon vector'),
        'lang' => 'en',
        'image_type' => $type,
        'order' => 'popular',
        'safesearch' => 'true',
        'per_page' => 24,
    ]);

    return response()->json($response->json());
});

Route::get('/api/learning-units', LearningUnitController::class)->name('api.learning-units');

Route::post('/api/tts/speech', TextToSpeechController::class)
    ->middleware('throttle:sensitive')
    ->name('api.tts.speech');

require __DIR__.'/auth.php';
