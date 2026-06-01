<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SpeechifyTtsService
{
    public function speech(string $text, array $options = []): array
    {
        $input = $this->normalizeText($text);

        if ($input === '') {
            throw new RuntimeException('لا يوجد نص صالح للقراءة.');
        }

        $config = $this->resolveConfig($options);
        $characterCount = mb_strlen($input);
        $path = $this->cachePath($input, $config);

        if (Storage::disk('public')->exists($path)) {
            return $this->responsePayload($path, true, $characterCount, $config);
        }

        $audio = $characterCount <= $config['max_chars']
            ? $this->createSpeech($input, $config)
            : $this->streamSpeech($input, $config);

        Storage::disk('public')->put($path, $audio);

        return $this->responsePayload($path, false, $characterCount, $config);
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function resolveConfig(array $options): array
    {
        $key = (string) config('services.speechify.key');

        if ($key === '') {
            throw new RuntimeException('خدمة القراءة الصوتية غير مفعلة حالياً.');
        }

        $format = strtolower((string) ($options['audio_format'] ?? config('services.speechify.audio_format', 'mp3')));
        $format = in_array($format, ['mp3', 'wav', 'ogg', 'aac'], true) ? $format : 'mp3';

        return [
            'key' => $key,
            'base_url' => rtrim((string) config('services.speechify.base_url', 'https://api.speechify.ai/v1'), '/'),
            'voice_id' => (string) ($options['voice_id'] ?? config('services.speechify.voice_id', 'george')),
            'model' => (string) ($options['model'] ?? config('services.speechify.model', 'simba-multilingual')),
            'language' => (string) ($options['language'] ?? config('services.speechify.language', 'ar-AE')),
            'audio_format' => $format,
            'max_chars' => max(100, (int) config('services.speechify.max_chars', 1900)),
            'stream_max_chars' => max(1000, (int) config('services.speechify.stream_max_chars', 19000)),
        ];
    }

    private function cachePath(string $input, array $config): string
    {
        $hash = hash('sha256', implode('|', [
            'speechify',
            $config['voice_id'],
            $config['model'],
            $config['language'],
            $config['audio_format'],
            $input,
        ]));

        return 'tts/'.$hash.'.'.$config['audio_format'];
    }

    private function createSpeech(string $input, array $config): string
    {
        $payload = [
            'input' => $input,
            'voice_id' => $config['voice_id'],
            'audio_format' => $config['audio_format'],
            'language' => $config['language'],
            'model' => $config['model'],
        ];

        try {
            $response = Http::timeout(35)
                ->withToken($config['key'])
                ->acceptJson()
                ->asJson()
                ->post($config['base_url'].'/audio/speech', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('تعذر الاتصال بخدمة القراءة الصوتية.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($this->speechifyErrorMessage($response->json('error'), $response->status()));
        }

        $audioData = $response->json('audio_data');

        if (! is_string($audioData) || $audioData === '') {
            throw new RuntimeException('لم ترجع خدمة القراءة الصوتية ملفاً صالحاً.');
        }

        $audio = base64_decode($audioData, true);

        if ($audio === false || $audio === '') {
            throw new RuntimeException('تعذر تجهيز ملف الصوت.');
        }

        return $audio;
    }

    private function streamSpeech(string $input, array $config): string
    {
        if (mb_strlen($input) > $config['stream_max_chars']) {
            $input = mb_substr($input, 0, $config['stream_max_chars']);
        }

        $mime = match ($config['audio_format']) {
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            default => 'audio/mpeg',
        };

        $payload = [
            'input' => $input,
            'voice_id' => $config['voice_id'],
            'language' => $config['language'],
            'model' => $config['model'],
        ];

        try {
            $response = Http::timeout(60)
                ->withToken($config['key'])
                ->accept($mime)
                ->asJson()
                ->post($config['base_url'].'/audio/stream', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('تعذر الاتصال بخدمة القراءة الصوتية.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException($this->speechifyErrorMessage($response->json('error'), $response->status()));
        }

        $audio = $response->body();

        if ($audio === '') {
            throw new RuntimeException('لم ترجع خدمة القراءة الصوتية ملفاً صالحاً.');
        }

        return $audio;
    }

    private function responsePayload(string $path, bool $cached, int $characters, array $config): array
    {
        return [
            'url' => '/storage/'.ltrim($path, '/'),
            'cached' => $cached,
            'characters' => $characters,
            'voice_id' => $config['voice_id'],
            'language' => $config['language'],
            'audio_format' => $config['audio_format'],
        ];
    }

    private function speechifyErrorMessage(mixed $error, int $status): string
    {
        $message = is_array($error) ? ($error['message'] ?? $error['detail'] ?? null) : $error;

        return match ($status) {
            401 => 'مفتاح خدمة القراءة الصوتية غير صحيح.',
            402 => 'رصيد خدمة القراءة الصوتية غير كاف.',
            429 => 'خدمة القراءة الصوتية مشغولة الآن، جرّب بعد لحظات.',
            default => is_string($message) && $message !== ''
                ? 'تعذر توليد الصوت: '.$message
                : 'تعذر توليد الصوت حالياً.',
        };
    }
}
