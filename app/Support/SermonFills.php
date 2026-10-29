<?php

namespace App\Support;

use App\Models\Sermon;
use App\Models\SermonFill;
use App\Models\User;

/** A member's fill-in-the-blank answers for a sermon (shared by web and API). */
class SermonFills
{
    public static function rules(Sermon $sermon): array
    {
        return [
            'key' => ['required', 'string', 'in:' . implode(',', $sermon->fillKeys() ?: ['-'])],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Saves a blank's answer; an empty value clears it. */
    public static function save(User $user, Sermon $sermon, string $key, ?string $value): void
    {
        $value = trim((string) $value);
        $where = ['user_id' => $user->id, 'sermon_id' => $sermon->id, 'fill_key' => $key];

        $value === ''
            ? SermonFill::where($where)->delete()
            : SermonFill::updateOrCreate($where, ['value' => $value]);
    }

    /** @return array<string,string> key => value */
    public static function forUser(?User $user, Sermon $sermon): array
    {
        if (! $user) {
            return [];
        }

        return SermonFill::where('user_id', $user->id)->where('sermon_id', $sermon->id)->pluck('value', 'fill_key')->all();
    }
}
