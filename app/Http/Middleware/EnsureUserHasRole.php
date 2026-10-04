<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Route-level role gate. Console-ica previously had no route-level
     * authorization at all — role checks were only ever done ad-hoc inside
     * individual controller methods (e.g. AuthController::callback,
     * UserController::changeAdminRole) — so any authenticated+verified user
     * could reach the admin routes regardless of role.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::user()?->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
