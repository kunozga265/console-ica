<?php

namespace App\Support;

use App\Models\Member;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/** Members whose birthday falls in the current week (church timezone). */
class Birthdays
{
    /** @return array<int, array{id:int, name:string, avatar:?string, cell:?string, date:int}> date in seconds */
    public static function thisWeek(): array
    {
        $tz = config('app.timezone');
        $week = [];
        foreach (CarbonPeriod::create(Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()) as $day) {
            $week[$day->format('m-d')] = $day->copy()->startOfDay();
        }

        return Member::with('cell')
            ->whereNotNull('date_of_birth')
            ->get(['id', 'first_name', 'last_name', 'avatar', 'date_of_birth', 'cell_id'])
            ->map(function (Member $member) use ($week, $tz) {
                $key = Carbon::createFromTimestamp($member->date_of_birth, $tz)->format('m-d');

                return isset($week[$key]) ? [
                    'id'     => intval($member->id),
                    'name'   => $member->fullName(),
                    'avatar' => $member->avatar,
                    'cell'   => $member->cell?->name,
                    'date'   => $week[$key]->getTimestamp(),
                ] : null;
            })
            ->filter()
            ->sortBy('date')
            ->values()
            ->all();
    }
}
