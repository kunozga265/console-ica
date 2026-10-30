<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\Register;
use App\Support\RegisterAttendance;
use Illuminate\Http\Request;

/**
 * QR / app-link check-in (https://www.ica-app.com/check-in/{code} opened in the
 * app). Same rules as the website's check-in page: only on the service day.
 */
class CheckInController extends Controller
{
    public function store(Request $request, string $code)
    {
        $register = Register::where('code', $code)->first();
        if (! $register) {
            return response()->json(['status' => 'not-found', 'message' => 'This check-in code isn’t valid.'], 404);
        }

        $info = ['name' => $register->name, 'date' => intval($register->date)];
        $member = $request->user()->member;

        if (! $member) {
            return response()->json(['status' => 'no-member', 'register' => $info,
                'message' => 'Your account isn’t linked to a member profile yet. Please see the Protocol Team.'], 409);
        }
        if (RegisterAttendance::isMarked($register, $member)) {
            return response()->json(['status' => 'already', 'register' => $info, 'message' => 'You’re already checked in.']);
        }
        if (! RegisterAttendance::isActive($register)) {
            return response()->json(['status' => 'inactive', 'register' => $info,
                'message' => 'Check-in for this service is only open on the day.'], 422);
        }

        $coords = array_filter($request->only(['latitude', 'longitude']), fn ($v) => $v !== null);
        RegisterAttendance::mark($register, $member->loadMissing('cell'), $coords ?: null);

        return response()->json(['status' => 'checked-in', 'register' => $info, 'message' => 'You’re checked in. Welcome!']);
    }
}
