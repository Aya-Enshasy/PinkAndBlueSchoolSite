<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SecureUploadService
{
    private const UPLOAD_DIRECTORY = 'uploads';

    private const DEFAULT_FOLDER_KEY = 'students';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /** @var int[] */
    private const ALLOWED_IMAGE_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG];

    public function storeImage(UploadedFile $file, string $fieldName, string $folderKey = self::DEFAULT_FOLDER_KEY): string
    {
        $this->validateImage($file, $fieldName);

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_string($realPath) || $realPath === '') {
            throw ValidationException::withMessages([$fieldName => 'Unable to process the uploaded file.']);
        }

        $folder = $this->folderFor($folderKey);
        $publicId = (string) Str::uuid();

        try {
            $result = retry(3, function () use ($realPath, $folder, $publicId) {
                return $this->cloudinary()->uploadApi()->upload($realPath, [
                    'folder' => $folder,
                    'public_id' => $publicId,
                    'resource_type' => 'image',
                    'overwrite' => false,
                    'use_filename' => false,
                    'unique_filename' => false,
                    'invalidate' => true,
                ]);
            }, 200);

            $secureUrl = (string) ($result['secure_url'] ?? '');
            if ($secureUrl === '') {
                throw new RuntimeException('Cloudinary did not return a secure URL.');
            }

            $optimizedUrl = $this->optimizedUrl($secureUrl);

            Log::channel('image_uploads')->info('Image uploaded to Cloudinary', [
                'field' => $fieldName,
                'folder' => $folder,
                'public_id' => $result['public_id'] ?? null,
                'bytes' => $file->getSize(),
            ]);

            return $optimizedUrl;
        } catch (Throwable $exception) {
            Log::channel('image_uploads')->error('Cloudinary upload failed', [
                'field' => $fieldName,
                'folder' => $folder,
                'message' => $exception->getMessage(),
                'mime' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'ip' => request()?->ip(),
            ]);

            $fallbackUrl = trim((string) config('cloudinary_uploads.fallback_image_url'));
            if ($fallbackUrl !== '') {
                return $fallbackUrl;
            }

            return $this->storeImageLocally($file, $fieldName, $folder, $publicId);
        }
    }

    public function deleteImage(?string $pathOrUrl): void
    {
        if (! $pathOrUrl) {
            return;
        }

        if ($this->isCloudinaryUrl($pathOrUrl)) {
            $this->deleteCloudinaryImage($pathOrUrl);
            return;
        }

        // Keep backward compatibility for old records that still point to local storage.
        if ($this->isSafeUploadPath($pathOrUrl)) {
            Storage::disk('public')->delete($pathOrUrl);
        }
    }

    private function validateImage(UploadedFile $file, string $fieldName): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->logUploadRejection($fieldName, 'extension_not_allowed', $file);
            throw ValidationException::withMessages([$fieldName => 'Only JPG, JPEG, and PNG images are allowed.']);
        }

        $maxBytes = (int) config('cloudinary_uploads.max_bytes', 2 * 1024 * 1024);
        $size = (int) $file->getSize();
        if ($size <= 0 || $size > $maxBytes) {
            $this->logUploadRejection($fieldName, 'size_not_allowed', $file);
            throw ValidationException::withMessages([$fieldName => 'Image size must not exceed 2MB.']);
        }

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_string($realPath) || $realPath === '') {
            $this->logUploadRejection($fieldName, 'invalid_temp_file_path', $file);
            throw ValidationException::withMessages([$fieldName => 'Unable to process the uploaded file.']);
        }

        $imageType = $this->detectImageType($realPath);
        if (! in_array($imageType, self::ALLOWED_IMAGE_TYPES, true)) {
            $this->logUploadRejection($fieldName, 'image_signature_not_allowed', $file);
            throw ValidationException::withMessages([$fieldName => 'The uploaded file is not a valid JPG or PNG image.']);
        }

        $mimeType = strtolower((string) $file->getMimeType());
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg', 'image/pjpeg', 'image/x-png'], true)) {
            $this->logUploadRejection($fieldName, 'mime_not_allowed', $file);
            throw ValidationException::withMessages([$fieldName => 'The uploaded file is not a valid JPG or PNG image.']);
        }

        $snippet = @file_get_contents($realPath, false, null, 0, 512);
        $snippet = is_string($snippet) ? $snippet : '';

        if ($snippet !== '' && preg_match('/<\?(php|=)|<script\b|MZ\x90|#!\/bin\//i', $snippet) === 1) {
            $this->logUploadRejection($fieldName, 'suspicious_content_detected', $file);
            throw ValidationException::withMessages([$fieldName => 'The uploaded file was rejected for security reasons.']);
        }
    }

    private function detectImageType(string $realPath): int|false
    {
        if (function_exists('exif_imagetype')) {
            $type = @exif_imagetype($realPath);
            if ($type !== false) {
                return $type;
            }
        }

        $imageInfo = @getimagesize($realPath);

        if (is_array($imageInfo) && isset($imageInfo[2]) && is_int($imageInfo[2])) {
            return $imageInfo[2];
        }

        return false;
    }

    private function folderFor(string $folderKey): string
    {
        $folders = (array) config('cloudinary_uploads.folders', []);
        $folder = (string) ($folders[$folderKey] ?? $folders[self::DEFAULT_FOLDER_KEY] ?? 'uploads/general');
        $folder = trim(str_replace('\\', '/', $folder), '/');

        if ($folder === '' || str_contains($folder, '..')) {
            return 'uploads/general';
        }

        return $folder;
    }

    private function optimizedUrl(string $secureUrl): string
    {
        $transformation = trim((string) config('cloudinary_uploads.delivery_transformation', ''));
        if ($transformation === '' || ! str_contains($secureUrl, '/image/upload/')) {
            return $secureUrl;
        }

        return str_replace('/image/upload/', '/image/upload/'.$transformation.'/', $secureUrl);
    }

    private function storeImageLocally(UploadedFile $file, string $fieldName, string $folder, string $publicId): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $path = $file->storeAs($folder, $publicId.'.'.$extension, 'public');

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([$fieldName => 'Image upload failed. Please try again.']);
        }

        Log::channel('image_uploads')->info('Image stored locally after Cloudinary fallback', [
            'field' => $fieldName,
            'path' => $path,
        ]);

        return $path;
    }

    private function isCloudinaryUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && str_ends_with(strtolower($host), 'cloudinary.com');
    }

    private function deleteCloudinaryImage(string $url): void
    {
        $publicId = $this->publicIdFromCloudinaryUrl($url);
        if ($publicId === null) {
            return;
        }

        try {
            retry(3, fn () => $this->cloudinary()->uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
                'invalidate' => true,
            ]), 200);

            Log::channel('image_uploads')->info('Image deleted from Cloudinary', [
                'public_id' => $publicId,
            ]);
        } catch (Throwable $exception) {
            Log::channel('image_uploads')->warning('Cloudinary delete failed', [
                'public_id' => $publicId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function publicIdFromCloudinaryUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $uploadIndex = array_search('upload', $segments, true);
        if ($uploadIndex === false) {
            return null;
        }

        $start = $uploadIndex + 1;
        for ($index = $start; $index < count($segments); $index++) {
            if (preg_match('/^v\d+$/', $segments[$index]) === 1) {
                $start = $index + 1;
                break;
            }
        }

        $publicSegments = array_slice($segments, $start);
        if ($publicSegments === []) {
            return null;
        }

        $publicId = implode('/', $publicSegments);

        return preg_replace('/\.[a-z0-9]+$/i', '', rawurldecode($publicId)) ?: null;
    }

    private function cloudinary(): Cloudinary
    {
        $this->ensureCloudinaryIsConfigured();

        return app(Cloudinary::class);
    }

    private function ensureCloudinaryIsConfigured(): void
    {
        if (filled(config('filesystems.disks.cloudinary.url'))) {
            return;
        }

        $cloudName = trim((string) config('filesystems.disks.cloudinary.cloud'));
        $apiKey = trim((string) config('filesystems.disks.cloudinary.key'));
        $apiSecret = trim((string) config('filesystems.disks.cloudinary.secret'));

        $missing = [];
        if ($cloudName === '') {
            $missing[] = 'CLOUDINARY_CLOUD_NAME';
        }
        if ($apiKey === '') {
            $missing[] = 'CLOUDINARY_API_KEY';
        }
        if ($apiSecret === '') {
            $missing[] = 'CLOUDINARY_API_SECRET';
        }

        if ($missing !== []) {
            throw new RuntimeException('Cloudinary is not configured. Missing: '.implode(', ', $missing).'.');
        }

        if ($cloudName === 'Root' || preg_match('/^[a-z0-9_-]+$/', $cloudName) !== 1) {
            throw new RuntimeException('Invalid CLOUDINARY_CLOUD_NAME. Use the Cloudinary cloud name, not the API key name.');
        }
    }

    private function isSafeUploadPath(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if (str_contains($normalizedPath, '../') || str_contains($normalizedPath, '..\\')) {
            return false;
        }

        return Str::startsWith($normalizedPath, self::UPLOAD_DIRECTORY.'/');
    }

    private function logUploadRejection(string $fieldName, string $reason, UploadedFile $file): void
    {
        $context = [
            'field' => $fieldName,
            'reason' => $reason,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'ip' => request()?->ip(),
        ];

        try {
            Log::channel('image_uploads')->warning('Upload rejected', $context);
        } catch (Throwable) {
            Log::warning('Upload rejected', $context);
        }
    }
}
