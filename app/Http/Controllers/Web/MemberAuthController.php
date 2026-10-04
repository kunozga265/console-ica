<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\MemberOnboarding;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Member onboarding after sign-in, mirroring the mobile app's V1_3
 * UserController login (406 "Please confirm if this is your member profile")
 * and confirm (EXISTING / NEW) steps.
 */
class MemberAuthController extends Controller
{
    /** Google sign-ups (and older accounts) give us no phone or gender — ask for them. */
    public function complete(Request $request)
    {
        $user = $request->user();
        if ($user->member_id || $request->session()->has(MemberOnboarding::CANDIDATE)) {
            return MemberOnboarding::next($request, $user);
        }

        return Inertia::render('Auth/CompleteProfile', [
            'profile' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'phoneNumberAirtel' => $user->phone_number_airtel,
                'phoneNumberTnm' => $user->phone_number_tnm,
                'phoneNumberInternational' => $user->phone_number_international,
            ],
        ]);
    }

    public function storeComplete(Request $request)
    {
        $user = $request->user();
        if ($user->member_id) {
            return MemberOnboarding::next($request, $user);
        }

        $v = $this->validateDetails($request, [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'first_name' => $v['firstName'],
            'last_name' => $v['lastName'],
            'phone_number_airtel' => $v['phoneNumberAirtel'] ?? null,
            'phone_number_tnm' => $v['phoneNumberTnm'] ?? null,
            'phone_number_international' => $v['phoneNumberInternational'] ?? null,
        ]);

        $candidate = MemberOnboarding::findCandidate($this->phones($v), $user->email);
        if ($candidate) {
            // Keep what they typed so "That's not me" doesn't ask again.
            $request->session()->put(MemberOnboarding::CANDIDATE, $candidate->id);
            $request->session()->put('member_link_details', [
                'gender' => $v['gender'],
                'dateOfBirth' => $v['dateOfBirth'] ?? null,
            ]);

            return redirect()->route('member-link.confirm');
        }

        $member = $this->createMember($user, $v, null);
        $user->update(['member_id' => $member->id]);

        return MemberOnboarding::next($request, $user)->with('success', 'Welcome to ICA!');
    }

    public function confirmLink(Request $request)
    {
        $candidate = Member::with('cell')->find($request->session()->get(MemberOnboarding::CANDIDATE));

        if (! $candidate || $request->user()->member_id) {
            $request->session()->forget(MemberOnboarding::CANDIDATE);

            return MemberOnboarding::next($request, $request->user());
        }

        $user = $request->user();

        return Inertia::render('Auth/ConfirmMemberLink', [
            'candidate' => [
                'name' => trim("{$candidate->first_name} {$candidate->last_name}"),
                'avatar' => $candidate->avatar,
                'cell' => $candidate->cell?->name,
                // Partially hidden — enough to recognise, not to harvest.
                'phone' => $this->mask($candidate->phone_number_airtel ?: $candidate->phone_number_tnm ?: $candidate->phone_number_international),
                'email' => $candidate->email ? preg_replace('/(?<=^.).*(?=@)/', '•••', $candidate->email) : null,
            ],
            // Details already collected (registration / complete-profile) pre-fill "not me".
            'details' => array_merge([
                'gender' => '',
                'dateOfBirth' => '',
                'phoneNumberAirtel' => $user->phone_number_airtel,
                'phoneNumberTnm' => $user->phone_number_tnm,
                'phoneNumberInternational' => $user->phone_number_international,
            ], array_filter($request->session()->get('member_link_details', []))),
        ]);
    }

    public function linkExisting(Request $request)
    {
        $candidateId = $request->session()->get(MemberOnboarding::CANDIDATE);
        $candidate = $candidateId ? Member::whereDoesntHave('user')->find($candidateId) : null;

        if ($candidate) {
            $request->user()->update(['member_id' => $candidate->id]);
        }
        $request->session()->forget([MemberOnboarding::CANDIDATE, 'member_link_details']);

        return MemberOnboarding::next($request, $request->user())->with('success', 'Profile linked!');
    }

    public function createNew(Request $request)
    {
        $candidate = Member::find($request->session()->get(MemberOnboarding::CANDIDATE));
        if (! $candidate) {
            return MemberOnboarding::next($request, $request->user());
        }

        $v = $this->validateDetails($request);
        $user = $request->user();

        $member = $this->createMember($user, $v, $candidate);
        $user->update(['member_id' => $member->id]);
        $request->session()->forget([MemberOnboarding::CANDIDATE, 'member_link_details']);

        return MemberOnboarding::next($request, $user)->with('success', 'New profile created!');
    }

    private function validateDetails(Request $request, array $extra = []): array
    {
        $v = $request->validate($extra + [
            'gender' => ['required', 'in:Male,Female'],
            'dateOfBirth' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'phoneNumberAirtel' => ['nullable', 'string', 'max:20'],
            'phoneNumberTnm' => ['nullable', 'string', 'max:20'],
            'phoneNumberInternational' => ['nullable', 'string', 'max:20'],
        ]);

        if (! array_filter($this->phones($v))) {
            back()->withErrors(['phoneNumberAirtel' => 'Please provide at least one phone number.'])->throwResponse();
        }

        return $v;
    }

    private function phones(array $v): array
    {
        return [
            'phone_number_airtel' => $v['phoneNumberAirtel'] ?? null,
            'phone_number_tnm' => $v['phoneNumberTnm'] ?? null,
            'phone_number_international' => $v['phoneNumberInternational'] ?? null,
        ];
    }

    private function createMember($user, array $v, ?Member $candidate): Member
    {
        $phones = $this->phones($v);
        if ($candidate) {
            // Like the mobile confirm-NEW flow: don't duplicate numbers that
            // belong to the profile they said isn't them.
            foreach ($phones as $column => $number) {
                if ($number === $candidate->{$column}) {
                    $phones[$column] = null;
                }
            }
        }

        return Member::create($phones + [
            'code' => (new AppController())->generateUniqueCode(),
            // The mobile app prefixes member avatars with its base URL, so keep them relative.
            'avatar' => $user->avatar && ! str_starts_with($user->avatar, 'http') ? $user->avatar : 'images/avatar.png',
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'gender' => $v['gender'],
            'date_of_birth' => MemberOnboarding::birthTimestamp($v['dateOfBirth'] ?? null),
        ]);
    }

    private function mask(?string $phone): ?string
    {
        return $phone ? '•••• '.substr($phone, -3) : null;
    }
}
