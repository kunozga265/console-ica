<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\API\V1_3\AppController as V1_3AppController;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\UserResource;
use App\Support\Birthdays;
use Illuminate\Http\Request;

/** API 1.4 dashboard: the 1.3 sync payload, plus this week's birthdays and the current user (with scopes) when signed in. */
class AppController extends V1_3AppController
{
    public function dashboard(Request $request, $timestamp)
    {
        $response = parent::dashboard($request, $timestamp);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $data = $response->getData(true);
        $user = (new WebAppController())->getAuthUser($request);
        $data['birthdays'] = $user ? Birthdays::thisWeek() : [];
        // Always send the signed-in user, so scopes granted before an app update
        // reach the app without waiting for the account to change again.
        if ($user) {
            $data['user'] = (new UserResource($user))->resolve();
        }

        return response()->json($data);
    }
}
