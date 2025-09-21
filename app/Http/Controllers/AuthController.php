<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect($provider)
    {
        $redirectUrl = Socialite::driver($provider)->redirect()->getTargetUrl();
        return response('', 409)->header('X-Inertia-Location', $redirectUrl);
    }

    public function callback($provider)
    {
        $social_user = Socialite::driver($provider)->user();

        $user = User::where('email', $social_user->email)->first();

        if (is_object($user)) {
            if ($user->hasRole("admin") || $user->hasRole("super")) {
                Auth::login($user);
                
                return Redirect::route("dashboard");
            } else {
                return Redirect::back()->withErrors(["User not an adminstrator"]);
            }
        } else {
            return Redirect::back()->withErrors(["Email is not registered"]);
        }

        // dd($user);
    }
}
