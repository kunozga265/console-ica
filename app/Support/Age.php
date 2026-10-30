<?php

namespace App\Support;

use Carbon\Carbon;

/** Children and teenagers: anyone 18 or under (church timezone). */
class Age
{
    public const MINOR_MAX_AGE = 18;

    /** @param int|string|null $dateOfBirth unix timestamp or Y-m-d */
    public static function isMinor(int|string|null $dateOfBirth): bool
    {
        if ($dateOfBirth === null || $dateOfBirth === '') {
            return false;
        }
        $tz = config('app.timezone');
        try {
            $dob = is_numeric($dateOfBirth)
                ? Carbon::createFromTimestamp((int) $dateOfBirth, $tz)
                : Carbon::createFromFormat('Y-m-d', (string) $dateOfBirth, $tz);
        } catch (\Throwable) {
            return false;
        }

        // Whole years of age, so an 18-year-old stays a minor until their 19th birthday.
        return $dob->isPast() && $dob->age <= self::MINOR_MAX_AGE;
    }
}
