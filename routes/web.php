<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlannerItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.portal');
})->name('role.portal');

Route::get('/student', function () {
    return view('welcome', ['initialView' => 'student']);
})->name('student.platform');

Route::get('/teacher', function () {
    return view('welcome', ['initialView' => 'teacher']);
})->name('teacher.platform');

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

require __DIR__.'/auth.php';
