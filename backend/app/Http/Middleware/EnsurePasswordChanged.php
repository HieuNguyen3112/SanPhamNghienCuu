<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        $allowedPaths = [
            'auth/me',
        ];

        if (in_array($path, $allowedPaths, true)) {
            return $next($request);
        }

        return response()->json([
            'code' => 'PASSWORD_CHANGE_REQUIRED',
            'message' => 'You must change password before using the system.',
        ], Response::HTTP_FORBIDDEN);
    }
}
