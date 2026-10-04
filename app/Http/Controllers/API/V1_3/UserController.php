<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\V1_3\BookmarkResource;
use App\Http\Resources\HighlightResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\NoteResource;
use App\Models\Role;
use App\Models\User;
use App\Models\Member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function login(Request $request)
    {

        $request->validate([
            "email" => ["required"],
            "password" => ["required"],
            "device_name" => ["required"],
        ]);


        $user = User::where('email', $request->email)->first();

        if (is_object($user) && $user?->member_id != null) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(["message" => "Incorrect password"], 400);
                //                    throw ValidationException::withMessages([
                //                        'email' => ['The provided credentials are incorrect.'],
                //                    ]);
            }
        } else {
            if (!isset($request->name)) {
                return response()->json(["message" => "Please sign up with profile name"], 404);
            } else if ((!isset($request->phone_number_airtel) && !isset($request->phone_number_tnm) && !isset($request->phone_number_international))) {
                return response()->json(["message" => "Please sign up with at least one phone number"], 404);
            } else if (!isset($request->gender)) {
                return response()->json(["message" => "Please enter your gender"], 404);
            }

            $splitNames = explode(" ", $request->name);
            $first_name = $splitNames[0];
            $last_name = end($splitNames);

            $user = User::updateOrCreate(
                ['email' => $request->email], // lookup condition
                [
                    'avatar' => $request->avatar ?? env('APP_URL') . 'images/avatar.png',
                    'first_name' => $first_name,
                    'last_name' => $first_name !== $last_name ? $last_name : null,
                    'phone_number_airtel' => $request->phone_number_airtel,
                    'phone_number_tnm' => $request->phone_number_tnm,
                    'phone_number_international' => $request->phone_number_international,
                    'password' => Hash::make($request->password),
                ]
            );

            $role = Role::where("name", "normal")->first();
            $user->roles()->attach($role);

            //check and attach a member
            $member = Member::query()
                ->when($request->filled('phone_number_airtel'), function ($q) use ($request) {
                    $q->orWhere('phone_number_airtel', $request->phone_number_airtel);
                })
                ->when($request->filled('phone_number_tnm'), function ($q) use ($request) {
                    $q->orWhere('phone_number_tnm', $request->phone_number_tnm);
                })
                ->when($request->filled('phone_number_international'), function ($q) use ($request) {
                    $q->orWhere('phone_number_international', $request->phone_number_international);
                })
                ->when($request->filled('email'), function ($q) use ($request) {
                    $q->orWhere('email', $request->email);
                })
                ->first();

            if (is_object($member)) {

                return response()->json([
                    "message" => "Please confirm if this is your member profile",
                    "member" => new MemberResource($member),
                    "user" => new UserResource($user)
                ], 406);
                // $user->update([
                //     "member_id" => $member->id
                // ]);
            } else {
                $member = Member::create([
                    "code" => (new \App\Http\Controllers\Web\AppController())->generateUniqueCode(),
                    'avatar' => $request->avatar ?? "images/avatar.png",
                    'first_name' => ucwords($splitNames[0]),
                    'last_name' => $first_name != $last_name ? ucwords($last_name) : null,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'phone_number_airtel' => $request->phone_number_airtel,
                    'phone_number_tnm' => $request->phone_number_tnm,
                    'phone_number_international' => $request->phone_number_international,
                    'email' => $request->email,
                ]);

                $user->update([
                    "member_id" => $member->id
                ]);
            }
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }
    public function confirm(Request $request)
    {

        $request->validate([
            "type" => ["required"],
            "email" => ["required"],
            "gender" => ["required"],
            "member_id" => ["required"],
            "device_name" => ["required"],
        ]);


        $user = User::where('email', $request->email)->first();

        if (is_object($user)) {

            if ($request->type == "EXISTING") {
                $user->update([
                    "member_id" => $request->member_id
                ]);
            } else if ($request->type == "NEW") {

                if ((!isset($request->phone_number_airtel) && !isset($request->phone_number_tnm) && !isset($request->phone_number_international))) {
                    return response()->json(["message" =>  "Please sign up with at least one phone number"], 404);
                } else if (!isset($request->gender)) {
                    return response()->json(["message" =>  "Please enter your gender"], 404);
                }

                $member = Member::where("id", $request->member_id)->first();

                $new_member = Member::create([
                    "code" => (new \App\Http\Controllers\Web\AppController())->generateUniqueCode(),
                    'avatar' => $user->avatar,
                    'first_name' => ucwords($user->first_name),
                    'last_name' => ucwords($user->last_name),
                    'email' => $user->email,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'phone_number_airtel' => $request->phone_number_airtel != $member->phone_number_airtel ? $request->phone_number_airtel : null,
                    'phone_number_tnm' => $request->phone_number_tnm != $member->phone_number_tnm ? $request->phone_number_tnm : null,
                    'phone_number_international' => $request->phone_number_international != $member->phone_number_international ? $request->phone_number_international : null,
                ]);

                $user->update([
                    "member_id" => $new_member->id
                ]);
            }
        } else {
            return response()->json(["message" =>  "An error occurred while confirming member profile"], 404);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }

    public function update(Request $request)
    {

        $request->validate([
            "first_name" => ["required"],
            "last_name" => ["required"],
            "gender" => ["required"],
        ]);


        $user = User::where('email', $request->email)->first();

        if (is_object($user)) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(["message" =>  "Incorrect password"], 400);
                //                    throw ValidationException::withMessages([
                //                        'email' => ['The provided credentials are incorrect.'],
                //                    ]);
            }
        } else {
            if (!isset($request->name)) {
                return response()->json(["message" =>  "Please sign up with profile name"], 404);
            } else if ((!isset($request->phone_number_airtel) && !isset($request->phone_number_tnm) && !isset($request->phone_number_international))) {
                return response()->json(["message" =>  "Please sign up with at least one phone number"], 404);
            } else if (!isset($request->gender)) {
                return response()->json(["message" =>  "Please enter your gender"], 404);
            }

            $splitNames = explode(" ", $request->name);
            $first_name = $splitNames[0];
            $last_name = end($splitNames);

            $user = User::create([
                'avatar' => $request->avatar ?? env('APP_URL') . "images/avatar.png",
                'first_name' => $splitNames[0],
                'last_name' => $first_name != $last_name ? $last_name : null,
                'email' => $request->email,
                'phone_number_airtel' => $request->phone_number_airtel,
                'phone_number_tnm' => $request->phone_number_tnm,
                'phone_number_international' => $request->phone_number_international,
                'password' => Hash::make($request->password),
            ]);

            $role = Role::where("name", "normal")->first();
            $user->roles()->attach($role);

            //check and attach a member
            $member = Member::where("phone_number_airtel", $request->phone_number_airtel)
                ->orWhere("phone_number_tnm", $request->phone_number_tnm)
                ->orWhere("phone_number_international", $request->phone_number_international)
                ->first();

            if (is_object($member)) {
                $user->update([
                    "member_id" => $member->id
                ]);
            } else {
                Member::create([
                    "code" => (new \App\Http\Controllers\Web\AppController())->generateUniqueCode(),
                    'avatar' => $request->avatar ?? env('APP_URL') . "images/avatar.png",
                    'first_name' => ucwords($splitNames[0]),
                    'last_name' => $first_name != $last_name ? ucwords($last_name) : null,
                    'gender' => $request->gender,
                    'phone_number_airtel' => $request->phone_number_airtel,
                    'phone_number_tnm' => $request->phone_number_tnm,
                    'phone_number_international' => $request->phone_number_international,
                ]);
            }
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }
}
