<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTeacher()) {
            abort(403, 'غير مصرح لك بالدخول لهذه الصفحة.');
        }

        return $next($request);
    }
}
