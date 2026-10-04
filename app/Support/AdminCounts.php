<?php

namespace App\Support;

use App\Models\Author;
use App\Models\Cell;
use App\Models\Event;
use App\Models\Member;
use App\Models\Prayer;
use App\Models\Series;
use App\Models\Sermon;

/** Badge counts shown in the admin sidebar. */
class AdminCounts
{
    public static function get(): array
    {
        $now = now()->getTimestamp();

        return [
            'sermons'   => Sermon::where('published_at', '<=', $now)->count(),
            'series'    => Series::count(),
            'ministers' => Author::count(),
            'members'   => Member::count(),
            'events'    => Event::where('end_date', '>=', $now)->count(),
            'prayer'    => Prayer::count(),
            'cells'     => Cell::where('verified', false)->count(), // awaiting verification
        ];
    }
}
