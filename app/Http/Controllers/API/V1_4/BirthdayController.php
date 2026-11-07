<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Birthdays;
use App\Support\BirthdayWishes;
use Illuminate\Http\Request;

/** This week's birthdays and "wish them a happy birthday". */
class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => Birthdays::thisWeek($request->user()->id)]);
    }

    public function wish(Request $request, int $member)
    {
        BirthdayWishes::send($request->user(), Member::findOrFail($member));

        return response()->json(['wished' => true]);
    }
}
