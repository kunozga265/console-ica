<?php

namespace App\Support;

use App\Models\Sermon;
use App\Models\User;
use App\Models\View;
use Illuminate\Http\Request;

/**
 * Sermon view counting shared by the website and the mobile API.
 * `views` holds one row per (sermon, user) with a running count; signed-out
 * opens accumulate on the sermon's user_id = NULL row.
 */
class SermonViews
{
    public static function record(Sermon $sermon, ?User $user): void
    {
        $row = View::where('sermon_id', $sermon->id)->where('user_id', $user?->id);

        // Atomic +1; create the row the first time this user (or anyone anonymous) opens it.
        if (! (clone $row)->increment('count')) {
            View::create(['sermon_id' => $sermon->id, 'user_id' => $user?->id, 'count' => 1]);
        }
    }

    /**
     * A real page open? Inertia partial reloads (e.g. after highlighting a line),
     * prefetches and crawlers aren't views.
     */
    public static function isGenuineOpen(Request $request): bool
    {
        if ($request->header('X-Inertia-Partial-Data') !== null || $request->header('Purpose') === 'prefetch'
            || $request->header('Sec-Purpose') !== null) {
            return false;
        }

        return ! preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|monitor|curl|wget/i', (string) $request->userAgent());
    }
}
