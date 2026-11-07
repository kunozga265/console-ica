<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\API\V1_3\UserController as V1_3UserController;
use App\Support\FirebaseToken;
use Illuminate\Http\Request;

/**
 * 1.4 login: the same flow as 1.3, but Google / Apple sign-ins from app 1.4.2+
 * also send the Firebase ID token (`id_token`), which must match the email and
 * uid they claim. Older apps don't send one and keep the 1.3 behaviour.
 */
class UserController extends V1_3UserController
{
    public function login(Request $request)
    {
        if ($request->filled('id_token')) {
            try {
                $claims = FirebaseToken::verify((string) $request->input('id_token'));
            } catch (\Throwable) {
                return response()->json(['message' => 'Sign-in could not be verified. Please try again.'], 401);
            }

            $email = $claims['email'] ?? null;
            if ($claims['sub'] !== $request->input('password') || ($email && strcasecmp($email, (string) $request->input('email')) !== 0)) {
                return response()->json(['message' => 'Sign-in could not be verified. Please try again.'], 401);
            }
        }

        return parent::login($request);
    }
}
