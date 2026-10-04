<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\BookmarkResource;
use App\Models\Role;
use App\Models\User;
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

        if (is_object($user)) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(["message", "Incorrect password"], 400);
//                    throw ValidationException::withMessages([
//                        'email' => ['The provided credentials are incorrect.'],
//                    ]);
            }


        } else {
            if (!isset($request->name)) {
                return response()->json(["message", "Please sign up with profile name"], 404);
            }

            $splitNames = explode(" ", $request->name);
            $first_name = $splitNames[0];
            $last_name = end($splitNames);

            $user = User::create([
                'avatar' => isset($request->avatar) ? $request->avatar : env('APP_URL')."images/avatar.png",
                'first_name' => $splitNames[0],
                'last_name' => $first_name != $last_name ? $last_name : null,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $role = Role::where("name", "normal")->first();
            $user->roles()->attach($role);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            "highlights" => $user->highlights,
            "bookmarks" => BookmarkResource::collection( $user->bookmarks),
            "notes" => $user->notes,
        ]);
    }
}
