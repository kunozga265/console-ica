<?php

namespace App\Actions\Fortify;

use App\Http\Controllers\Web\AppController;
use App\Models\Member;
use App\Models\User;
use App\Support\MemberOnboarding;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * This app's `users` table has no `name` column (first_name/last_name
     * instead) and members are matched by phone/email, so the stock
     * Jetstream action (which assumes a `name` column and does no member
     * linking) can't be used as-is here.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            "firstName" => ["required", "string", "max:255"],
            "lastName" => ["required", "string", "max:255"],
            "email" => ["required", "string", "email", "max:255", "unique:users"],
            "password" => $this->passwordRules(),
            "gender" => ["required", "string"],
            "phoneNumberAirtel" => ["nullable", "string", "max:20"],
            "phoneNumberTnm" => ["nullable", "string", "max:20"],
            "phoneNumberInternational" => ["nullable", "string", "max:20"],
            "dateOfBirth" => ["nullable", "date_format:Y-m-d", "before:today"],
        ])->validate();

        if (
            empty($input["phoneNumberAirtel"])
            && empty($input["phoneNumberTnm"])
            && empty($input["phoneNumberInternational"])
        ) {
            Validator::make([], [])->after(function ($validator) {
                $validator->errors()->add("phoneNumberAirtel", "Please provide at least one phone number.");
            })->validate();
        }

        $user = User::create([
            "first_name" => $input["firstName"],
            "last_name" => $input["lastName"],
            "email" => $input["email"],
            "password" => Hash::make($input["password"]),
            "phone_number_airtel" => $input["phoneNumberAirtel"] ?? null,
            "phone_number_tnm" => $input["phoneNumberTnm"] ?? null,
            "phone_number_international" => $input["phoneNumberInternational"] ?? null,
        ]);

        MemberOnboarding::attachNormalRole($user);

        $candidate = MemberOnboarding::findCandidate([
            "phone_number_airtel" => $input["phoneNumberAirtel"] ?? null,
            "phone_number_tnm" => $input["phoneNumberTnm"] ?? null,
            "phone_number_international" => $input["phoneNumberInternational"] ?? null,
        ], $input["email"]);

        if ($candidate) {
            // Don't auto-link a possibly-different person's member profile —
            // stash the candidate so the post-login response can send the
            // user to a confirm-link screen instead.
            session([
                MemberOnboarding::CANDIDATE => $candidate->id,
                // Pre-fills "That's not me" so they aren't asked twice.
                "member_link_details" => ["gender" => $input["gender"], "dateOfBirth" => $input["dateOfBirth"] ?? null],
            ]);
        } else {
            $member = Member::create([
                "code" => (new AppController())->generateUniqueCode(),
                "first_name" => $input["firstName"],
                "last_name" => $input["lastName"],
                "gender" => $input["gender"],
                "email" => $input["email"],
                "phone_number_airtel" => $input["phoneNumberAirtel"] ?? null,
                "phone_number_tnm" => $input["phoneNumberTnm"] ?? null,
                "phone_number_international" => $input["phoneNumberInternational"] ?? null,
                "avatar" => "images/avatar.png",
                "date_of_birth" => MemberOnboarding::birthTimestamp($input["dateOfBirth"] ?? null),
            ]);

            $user->update(["member_id" => $member->id]);
        }

        return $user;
    }
}
