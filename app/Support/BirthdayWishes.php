<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** "So-and-so wishes you a happy birthday": one wish per sender per member per year. */
class BirthdayWishes
{
    public static function send(User $from, Member $member): void
    {
        $birthday = collect(Birthdays::thisWeek())->firstWhere('id', intval($member->id));
        if (! $birthday) {
            throw ValidationException::withMessages(['member' => "It isn't {$member->first_name}'s birthday this week."]);
        }

        $recipients = $member->users()->pluck('id')->reject(fn ($id) => $id === $from->id);
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['member' => "{$member->first_name} isn't on the ICA app yet, so we can't deliver a wish."]);
        }

        $year = now(config('app.timezone'))->year;
        $inserted = DB::table('birthday_wishes')->insertOrIgnore([
            'from_user_id' => $from->id,
            'member_id'    => $member->id,
            'year'         => $year,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
        if (! $inserted) {
            return; // already wished this year
        }

        $name = trim("{$from->first_name} {$from->last_name}") ?: 'Someone at ICA';
        Notify::users($recipients, "{$name} wishes you a happy birthday! 🎉", 'Tap to say thank you, and have a blessed day.', 'birthday', (string) $member->id);
    }
}
