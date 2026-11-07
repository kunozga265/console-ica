<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Support\ChurchTime;
use App\Support\MemberAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard's homecell card: the user's cell, its next meeting (if one is
 * scheduled), when they last attended, and whether they lead it (leaders can
 * schedule the next meeting).
 */
class MyCellController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $member = $user->member;
        $cell = $member?->cell;

        if (! $cell) {
            return response()->json(['cell' => null, 'hasMember' => $member !== null]);
        }

        $todayStart = Carbon::today(config('app.timezone'))->getTimestamp();
        $meeting = fn ($m) => $m ? ['code' => $m->code, 'date' => intval($m->date), 'day' => ChurchTime::day($m->date), 'venue' => $m->venue] : null;

        $next = DB::table('meetings')->where('cell_id', $cell->id)->whereRaw('CAST(date AS UNSIGNED) >= ?', [$todayStart])
            ->orderByRaw('CAST(date AS UNSIGNED)')->first(['code', 'date', 'venue']);
        $lastMeeting = DB::table('meetings')->where('cell_id', $cell->id)->whereRaw('CAST(date AS UNSIGNED) < ?', [$todayStart])
            ->orderByRaw('CAST(date AS UNSIGNED) DESC')->first(['code', 'date', 'venue']);
        $lastAttended = DB::table('attendances')->join('meetings', 'meetings.id', '=', 'attendances.meeting_id')
            ->where('attendances.member_id', $member->id)
            ->orderByRaw('CAST(meetings.date AS UNSIGNED) DESC')->first(['meetings.code', 'meetings.date', 'meetings.venue']);

        return response()->json([
            'cell' => ['code' => $cell->code, 'name' => $cell->name],
            'isLeader'     => MemberAccess::leadsCell($user, $cell),
            'nextMeeting'  => $meeting($next),
            'lastMeeting'  => $meeting($lastMeeting),
            'lastAttended' => $meeting($lastAttended),
        ]);
    }
}
