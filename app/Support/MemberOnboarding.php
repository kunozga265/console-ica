<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The web equivalent of the mobile app's V1_3 UserController login/confirm
 * flow: every signed-in user must end up linked to a Member. After any sign-in
 * (Google, email + password or registration) the user is sent to whichever
 * step they still need, and finally back to the page they originally asked
 * for — e.g. the attendance QR link they scanned.
 */
class MemberOnboarding
{
    public const CANDIDATE = 'member_link_candidate_id';

    public static function next(Request $request, User $user): RedirectResponse
    {
        if ($user->member_id) {
            $request->session()->forget(self::CANDIDATE);

            return redirect()->intended(route('ui.dashboard'));
        }

        if ($request->session()->has(self::CANDIDATE)) {
            return redirect()->route('member-link.confirm');
        }

        return redirect()->route('member-link.complete');
    }

    /**
     * A member profile that looks like this person (same phone or email) and
     * isn't already claimed by another account.
     */
    public static function findCandidate(array $phones, ?string $email): ?Member
    {
        $phones = array_filter($phones);
        if (! $phones && ! $email) {
            return null;
        }

        return Member::query()
            ->whereDoesntHave('user')
            ->where(function ($q) use ($phones, $email) {
                foreach ($phones as $column => $number) {
                    $q->orWhere($column, $number);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();
    }

    /** Members store date of birth as a local-midnight unix timestamp. */
    public static function birthTimestamp(?string $date): ?int
    {
        return $date
            ? Carbon::createFromFormat('Y-m-d', $date, config('app.timezone'))->startOfDay()->getTimestamp()
            : null;
    }

    public static function attachNormalRole(User $user): void
    {
        $role = Role::where('name', 'normal')->first();
        if ($role && ! $user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role);
        }
    }
}
