<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Register;
use Carbon\Carbon;

/**
 * Marking and unmarking a member on a service register — shared by the admin
 * attendance sheet and member QR self check-in. Mirrors the record shape of
 * API\V2\RegisterController (zone from the member's cell, meta coordinates).
 */
class RegisterAttendance
{
    /** A register can only be marked on its own day. */
    public static function isActive(Register $register): bool
    {
        return Carbon::createFromTimestamp($register->date, config('app.timezone'))->isToday();
    }

    public static function isMarked(Register $register, Member $member): bool
    {
        return Attendance::where('register_id', $register->id)->where('member_id', $member->id)->exists();
    }

    /** Returns true when a new attendance record was created. */
    public static function mark(Register $register, Member $member, array $coordinates = null): bool
    {
        if (self::isMarked($register, $member)) {
            return false;
        }

        Attendance::create([
            'member_id'   => $member->id,
            'register_id' => $register->id,
            'zone_id'     => $member->cell?->zone_id,
            'date'        => now()->getTimestamp(),
            'meta'        => json_encode(['coordinates' => $coordinates]),
        ]);

        return true;
    }

    public static function unmark(Register $register, Member $member): void
    {
        Attendance::where('register_id', $register->id)->where('member_id', $member->id)->delete();
    }
}
