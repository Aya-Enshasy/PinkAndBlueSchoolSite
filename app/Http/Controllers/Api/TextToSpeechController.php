<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SpeechifyTtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TextToSpeechController extends Controller
{
    public function __invoke(Request $request, SpeechifyTtsService $tts): JsonResponse
    {
        $payload = $request->validate([
            'text' => ['required', 'string', 'max:19000'],
            'language' => ['nullable', 'string', 'max:12'],
            'voice_id' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            return response()->json($tts->speech($payload['text'], [
                'language' => $payload['language'] ?? null,
                'voice_id' => $payload['voice_id'] ?? null,
            ]));
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }
    }
}
