<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TextToSpeechTest extends TestCase
{
    use WithoutMiddleware;

    public function test_it_generates_and_caches_speechify_audio(): void
    {
        Storage::fake('public');

        config([
            'services.speechify.key' => 'test-key',
            'services.speechify.base_url' => 'https://api.speechify.ai/v1',
            'services.speechify.voice_id' => 'george',
            'services.speechify.model' => 'simba-multilingual',
            'services.speechify.language' => 'ar-AE',
            'services.speechify.audio_format' => 'mp3',
            'services.speechify.max_chars' => 1900,
        ]);

        Http::fake([
            'api.speechify.ai/v1/audio/speech' => Http::response([
                'audio_data' => base64_encode('fake-audio'),
                'audio_format' => 'mp3',
            ]),
        ]);

        $first = $this->postJson('/api/tts/speech', [
            'text' => 'مرحبا بكم',
            'language' => 'ar-AE',
        ]);

        $first->assertOk()
            ->assertJson([
                'cached' => false,
                'characters' => 9,
                'language' => 'ar-AE',
                'audio_format' => 'mp3',
            ]);

        $url = $first->json('url');
        $this->assertIsString($url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));

        $second = $this->postJson('/api/tts/speech', [
            'text' => 'مرحبا بكم',
            'language' => 'ar-AE',
        ]);

        $second->assertOk()
            ->assertJson([
                'cached' => true,
                'url' => $url,
            ]);

        Http::assertSentCount(1);
    }
}
