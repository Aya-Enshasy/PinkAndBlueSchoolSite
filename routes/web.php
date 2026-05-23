<?php

use Illuminate\Http\Request;
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
