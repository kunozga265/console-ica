<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\V2\BookmarkResource;
use App\Http\Resources\V2\HighlightResource;
use App\Http\Resources\V2\MemberResource;
use App\Http\Resources\V2\NoteResource;
use App\Http\Resources\V2\UserResource;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Same two-step "confirm your member profile" precedent already proven
     * in the mobile app (API\V1_3\UserController::login/confirm), cleaned
     * up: one casing convention (camelCase, fixing the V1_2 snake_case vs
     * V1_3 camelCase drift), and the same member-search logic factored so
     * both login-as-signup and confirm share it.
     */
    public function login(Request $request)
    {
        $request->validate([
            "email" => ["required", "email"],
            "password" => ["required"],
            "deviceName" => ["required"],
        ]);

        $user = User::where("email", $request->email)->first();

        if ($user && $user->member_id !== null) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(["message" => "Incorrect password"], 400);
            }

            return $this->authResponse($user, $request->deviceName);
        }

        // Signup path: no existing account, or an account not yet linked to a member.
        $request->validate([
            "firstName" => ["required", "string"],
            "lastName" => ["required", "string"],
            "gender" => ["required", "string"],
        ]);

        if (
            !$request->filled("phoneNumberAirtel")
            && !$request->filled("phoneNumberTnm")
            && !$request->filled("phoneNumberInternational")
        ) {
            return response()->json(["message" => "Please sign up with at least one phone number"], 404);
        }

        $user = User::updateOrCreate(
            ["email" => $request->email],
            [
                "first_name" => $request->firstName,
                "last_name" => $request->lastName,
                "phone_number_airtel" => $request->phoneNumberAirtel,
                "phone_number_tnm" => $request->phoneNumberTnm,
                "phone_number_international" => $request->phoneNumberInternational,
                "password" => Hash::make($request->password),
            ]
        );

        if (!$user->hasRole("normal")) {
            $role = Role::where("name", "normal")->first();
            if ($role) {
                $user->roles()->attach($role);
            }
        }

        $candidate = $this->findCandidateMember($request);

        if ($candidate) {
            return response()->json([
                "message" => "Please confirm if this is your member profile",
                "member" => new MemberResource($candidate),
                "user" => new UserResource($user),
            ], 406);
        }

        $member = Member::create([
            "code" => (new WebAppController())->generateUniqueCode(),
            "avatar" => "images/avatar.png",
            "first_name" => $request->firstName,
            "last_name" => $request->lastName,
            "gender" => $request->gender,
            "date_of_birth" => $request->dateOfBirth,
            "phone_number_airtel" => $request->phoneNumberAirtel,
            "phone_number_tnm" => $request->phoneNumberTnm,
            "phone_number_international" => $request->phoneNumberInternational,
            "email" => $request->email,
        ]);

        $user->update(["member_id" => $member->id]);

        return $this->authResponse($user, $request->deviceName);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            "type" => ["required", "in:EXISTING,NEW"],
            "email" => ["required", "email"],
            "deviceName" => ["required"],
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response()->json(["message" => "An error occurred while confirming member profile"], 404);
        }

        if ($request->type === "EXISTING") {
            $request->validate(["memberId" => ["required", "integer", "exists:members,id"]]);
            $user->update(["member_id" => $request->memberId]);
        } else {
            $request->validate([
                "memberId" => ["required", "integer", "exists:members,id"],
                "gender" => ["required", "string"],
            ]);

            $candidate = Member::find($request->memberId);

            $member = Member::create([
                "code" => (new WebAppController())->generateUniqueCode(),
                "avatar" => $user->avatar ?? "images/avatar.png",
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "gender" => $request->gender,
                "date_of_birth" => $request->dateOfBirth,
                "phone_number_airtel" => $request->phoneNumberAirtel !== $candidate?->phone_number_airtel ? $request->phoneNumberAirtel : null,
                "phone_number_tnm" => $request->phoneNumberTnm !== $candidate?->phone_number_tnm ? $request->phoneNumberTnm : null,
                "phone_number_international" => $request->phoneNumberInternational !== $candidate?->phone_number_international ? $request->phoneNumberInternational : null,
            ]);

            $user->update(["member_id" => $member->id]);
        }

        return $this->authResponse($user, $request->deviceName);
    }

    protected function findCandidateMember(Request $request): ?Member
    {
        return Member::query()
            ->when($request->filled("phoneNumberAirtel"), fn ($q) => $q->orWhere("phone_number_airtel", $request->phoneNumberAirtel))
            ->when($request->filled("phoneNumberTnm"), fn ($q) => $q->orWhere("phone_number_tnm", $request->phoneNumberTnm))
            ->when($request->filled("phoneNumberInternational"), fn ($q) => $q->orWhere("phone_number_international", $request->phoneNumberInternational))
            ->orWhere("email", $request->email)
            ->first();
    }

    protected function authResponse(User $user, string $deviceName)
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            "user" => new UserResource($user),
            "token" => $token,
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }
}
