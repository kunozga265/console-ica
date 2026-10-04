<?php

namespace App\Http\Middleware;

use App\Support\MemberOnboarding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasMember
{
    /**
     * A signed-in user with no linked Member is sent through onboarding
     * (confirm a matching profile, or complete a new one) and then brought
     * back to the page they were opening — e.g. a scanned check-in link.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->member_id === null) {
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                redirect()->setIntendedUrl($request->fullUrl());
            }

            return MemberOnboarding::next($request, $user);
        }

        return $next($request);
    }
}
