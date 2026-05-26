<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
