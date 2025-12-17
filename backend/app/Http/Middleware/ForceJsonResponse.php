<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Ensure the request/response cycle prefers JSON (avoid HTML redirects).
     */
    public function handle(Request $request, Closure $next)
    {
        // Hint to downstream middleware/exception handler that JSON is expected
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $next($request);
    }
}
