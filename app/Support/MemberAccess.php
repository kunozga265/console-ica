<?php

namespace App\Support;

use App\Models\Cell;
use App\Models\Member;
use App\Models\User;

/**
 * Who may manage which member records through the app (API 1.4):
 * the members scope; the registers scope for adding people at a service;
 * cell leaders for their own cell; and everyone for their own profile.
 */
class MemberAccess
{
    public static function leadsCell(User $user, ?Cell $cell): bool
    {
        return $cell !== null && (
            intval($user->member?->leader_cell_id) === $cell->id
            || intval($cell->user_id) === $user->id
        );
    }

    public static function leadsAnyCell(User $user): bool
    {
        return $user->member?->leader_cell_id !== null || Cell::where('user_id', $user->id)->exists();
    }

    /** Browse the member directory (picking people for a register or a cell). */
    public static function canList(User $user): bool
    {
        return $user->hasAnyScope('members', 'registers', 'cells') || self::leadsAnyCell($user);
    }

    /** Add a person, optionally into a cell. */
    public static function canCreate(User $user, ?Cell $cell): bool
    {
        return $user->hasAnyScope('members', 'registers') || ($cell && ($user->hasScope('cells') || self::leadsCell($user, $cell)));
    }

    public static function canUpdate(User $user, Member $member): bool
    {
        return $user->hasScope('members')
            || intval($user->member_id) === $member->id
            || self::leadsCell($user, $member->cell);
    }
}
