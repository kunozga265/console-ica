<?php

namespace App\Support;

use App\Models\Member;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/** Members whose birthday falls in the current week (church timezone). */
class Birthdays
{
    /**
     * @param int|null $viewerId adds `wished`: whether this user already sent them a wish this year
     * @return array<int, array{id:int, name:string, firstName:string, avatar:?string, cell:?string, date:int, day:string, canWish:bool, wished?:bool}>
     *         date in seconds (church midnight); day is the church date (Y-m-d), safe to show in any timezone
     */
    public static function thisWeek(?int $viewerId = null): array
    {
        $tz = config('app.timezone');
        $week = [];
        foreach (CarbonPeriod::create(Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()) as $day) {
            $week[$day->format('m-d')] = $day->copy()->startOfDay();
        }

        $wished = $viewerId
            ? DB::table('birthday_wishes')->where('from_user_id', $viewerId)->where('year', Carbon::now($tz)->year)->pluck('member_id')->map('intval')->flip()
            : collect();

        $list = Member::with(['cell', 'users:id,member_id'])
            ->whereNotNull('date_of_birth')
            ->get(['id', 'first_name', 'last_name', 'avatar', 'date_of_birth', 'cell_id'])
            ->map(function (Member $member) use ($week, $tz, $wished, $viewerId) {
                $key = Carbon::createFromTimestamp($member->date_of_birth, $tz)->format('m-d');

                if (! isset($week[$key])) {
                    return null;
                }
                $row = [
                    'id'        => intval($member->id),
                    'name'      => $member->fullName(),
                    'firstName' => trim($member->first_name),
                    'avatar'    => $member->avatar,
                    'cell'      => $member->cell?->name,
                    'date'      => $week[$key]->getTimestamp(),
                    'day'       => $week[$key]->toDateString(),
                    // Wishes reach the member through their account (inbox + push).
                    'canWish'   => $member->users->isNotEmpty() && ! $member->users->contains('id', $viewerId),
                ];
                if ($viewerId) {
                    $row['wished'] = $wished->has(intval($member->id));
                }

                return $row;
            })
            ->filter()
            ->sortBy('date')
            ->values();

        return $list->all();
    }
}
