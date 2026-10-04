<?php

namespace App\Http\Responses;

use App\Support\MemberOnboarding;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Where a user lands after Fortify login/register.
 *
 * Google sign-in (AuthController::callback) ends the same way, so every door
 * leads through member onboarding and then to the intended URL.
 */
class MemberAuthResponse implements LoginResponseContract, RegisterResponseContract
{
    public function toResponse($request)
    {
        // Member linked → back to the page they asked for (e.g. a scanned
        // check-in link); otherwise confirm a matching profile or complete one.
        return MemberOnboarding::next($request, $request->user());
    }
}
