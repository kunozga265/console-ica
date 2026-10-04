<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MemberOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect($provider)
    {
        abort_unless($provider === 'google', 404);

        // Works for both Inertia visits (409 + X-Inertia-Location) and plain links.
        return Inertia::location(Socialite::driver($provider)->redirect()->getTargetUrl());
    }

    /**
     * Anyone can sign in with Google: existing accounts are matched by email,
     * new ones are created (as in the mobile app's sign-up) and then onboarded
     * to a member profile before returning to the intended page.
     */
    public function callback(Request $request, $provider)
    {
        abort_unless($provider === 'google', 404);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('Google sign-in failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['email' => 'Google sign-in didn\'t complete. Please try again.']);
        }

        if (! $social->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => 'Your Google account didn\'t share an email address.']);
        }

        $user = User::where('email', $social->getEmail())->first();

        if (! $user) {
            [$first, $last] = $this->names($social);

            $user = User::create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $social->getEmail(),
                'avatar' => $social->getAvatar(),
                // Google accounts sign in without one; "Forgot password" can set one later.
                'password' => Hash::make(Str::random(40)),
            ]);
            MemberOnboarding::attachNormalRole($user);
        }

        // Remember the device so returning members aren't asked to sign in again.
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if (! $user->member_id && ! $request->session()->has(MemberOnboarding::CANDIDATE)) {
            // Google gives us no phone number, so the email is the only match we can try yet.
            if ($candidate = MemberOnboarding::findCandidate([], $user->email)) {
                $request->session()->put(MemberOnboarding::CANDIDATE, $candidate->id);
            }
        }

        return MemberOnboarding::next($request, $user);
    }

    private function names($social): array
    {
        $raw = $social->user ?? [];
        $first = $raw['given_name'] ?? null;
        $last = $raw['family_name'] ?? null;

        if (! $first) {
            $parts = preg_split('/\s+/', trim((string) $social->getName())) ?: [];
            $first = array_shift($parts) ?: Str::before($social->getEmail(), '@');
            $last = $last ?: implode(' ', $parts);
        }

        return [$first, $last ?? ''];
    }
}
