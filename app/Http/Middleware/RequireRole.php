<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()?->hasRole(...$roles)) {
            if (! $request->expectsJson()) {
                abort(403);
            }

            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
