<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * scope:members,registers — the user needs at least one of the listed scopes.
 * scope:any — the user needs at least one scope of any kind (the admin area).
 */
class EnsureUserHasScope
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $user = $request->user();
        $allowed = $user && ($scopes === ['any'] ? $user->hasAnyScope() : $user->hasAnyScope(...$scopes));

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => "You don't have access to this."], 403);
            }
            abort(403);
        }

        return $next($request);
    }
}
