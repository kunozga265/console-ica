<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Day boundaries in church time (Africa/Blantyre, UTC+2). Date-only values are
 * stored as either UTC or church midnight; both fall inside the church day, so
 * "up to and including today" is "before church midnight tonight".
 */
class ChurchTime
{
    public static function tomorrowTs(): int
    {
        return Carbon::tomorrow(config('app.timezone'))->getTimestamp();
    }

    /** "Y-m-d" of a timestamp in church time. */
    public static function day(int|float|string|null $timestamp): ?string
    {
        return $timestamp === null || $timestamp === '' ? null : Carbon::createFromTimestamp((int) $timestamp, config('app.timezone'))->toDateString();
    }
}
