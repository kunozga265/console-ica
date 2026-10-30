<?php

namespace App\Support;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Visitors are members with is_registered = false. Their visits are register
 * attendances; pastoral contact is logged as visitor_follow_ups.
 */
class Visitors
{
    /** Visitors with visit and follow-up summary columns. */
    public static function query(): Builder
    {
        $visits = fn () => DB::table('attendances')->join('registers', 'registers.id', '=', 'attendances.register_id')
            ->whereColumn('attendances.member_id', 'members.id');
        $latest = fn (string $column) => DB::table('visitor_follow_ups')->select($column)
            ->whereColumn('member_id', 'members.id')->orderByDesc('date')->orderByDesc('id')->limit(1);

        return Member::query()
            ->where('is_registered', false)
            ->select('members.*')
            ->selectSub($visits()->selectRaw('count(*)'), 'visits_count')
            ->selectSub($visits()->selectRaw('min(registers.date)'), 'first_visit')
            ->selectSub($visits()->selectRaw('max(registers.date)'), 'last_visit')
            ->selectSub($latest('date'), 'last_follow_up')
            ->selectSub($latest('next_date'), 'next_follow_up');
    }

    /** Needs attention: never followed up, or the next check-up date has arrived. */
    public static function needingFollowUp(Builder $query): Builder
    {
        $today = now(config('app.timezone'))->toDateString();

        return $query->where(fn ($q) => $q
            ->whereNotExists(fn ($e) => $e->from('visitor_follow_ups')->whereColumn('member_id', 'members.id'))
            ->orWhereRaw('(select next_date from visitor_follow_ups f where f.member_id = members.id order by f.date desc, f.id desc limit 1) <= ?', [$today]));
    }

    public static function dueCount(): int
    {
        return self::needingFollowUp(Member::query()->where('is_registered', false))->count();
    }
}
