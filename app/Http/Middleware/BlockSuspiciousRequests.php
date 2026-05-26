<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BlockSuspiciousRequests
{
    /** @var string[] */
    private array $patterns = [
        '/\.\.\//i',
        '/\.\.\\\\/i',
        '/<script\b/i',
        '/<\?(php|=)/i',
        '/\bunion\b.{0,20}\bselect\b/i',
        '/\b(base64_decode|eval|system|shell_exec)\s*\(/i',
        '/%00/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($request->is('students') || $request->is('students/*')) {
                return $next($request);
            }

            // Skip deep inspection for upload requests to avoid false positives
            // and heavy payload parsing on multipart/form-data.
            if ($request->files->count() > 0 || str_contains(strtolower((string) $request->header('content-type')), 'multipart/form-data')) {
                return $next($request);
            }

            $contentType = strtolower((string) $request->header('content-type'));
            $bodySnippet = '';

            if (! str_contains($contentType, 'multipart/form-data')) {
                $bodySnippet = substr((string) $request->getContent(), 0, 4000);
            }

            $payload = implode("\n", [
                $request->getRequestUri(),
                (string) $request->headers->get('user-agent'),
                $bodySnippet,
                json_encode($request->query(), JSON_UNESCAPED_UNICODE) ?: '',
            ]);

            foreach ($this->patterns as $pattern) {
                if (preg_match($pattern, $payload) === 1) {
                    $context = [
                        'pattern' => $pattern,
                        'path' => $request->path(),
                        'method' => $request->method(),
                        'ip' => $request->ip(),
                    ];

                    try {
                        Log::channel('security')->warning('Blocked suspicious request', $context);
                    } catch (Throwable) {
                        Log::warning('Blocked suspicious request', $context);
                    }

                    abort(403, 'Suspicious request detected.');
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Suspicious request middleware bypass due to exception', [
                'message' => $exception->getMessage(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }
}
