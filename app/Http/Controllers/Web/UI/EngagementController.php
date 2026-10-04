<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Prayer;
use App\Models\Sermon;
use App\Models\SermonSave;
use App\Models\UiNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Signed-in users' lightweight actions on the /ui pages. Each toggles state
 * and redirects back, so Inertia reloads the page's props.
 */
class EngagementController extends Controller
{
    /** Add/remove a sermon on the user's "saved" or "favorite" list. */
    public function toggleSermonSave(Request $request, Sermon $sermon)
    {
        $kind = $request->validate(['kind' => ['required', Rule::in(SermonSave::KINDS)]])['kind'];
        $attrs = ['sermon_id' => $sermon->id, 'user_id' => $request->user()->id, 'kind' => $kind];

        $existing = SermonSave::where($attrs)->first();
        $existing ? $existing->delete() : SermonSave::create($attrs);

        return back();
    }

    /** Attending (true), not attending (false), or clear (null). */
    public function respondToEvent(Request $request, Event $event)
    {
        $attending = $request->validate(['attending' => ['present', 'nullable', 'boolean']])['attending'];
        $userId = $request->user()->id;

        if ($attending === null) {
            EventResponse::where(['event_id' => $event->id, 'user_id' => $userId])->delete();
        } else {
            EventResponse::updateOrCreate(['event_id' => $event->id, 'user_id' => $userId], ['attending' => $attending]);
        }

        return back();
    }

    public function togglePraying(Request $request, Prayer $prayer)
    {
        $prayer->prayingUsers()->toggle($request->user()->id);

        return back();
    }

    public function readNotifications(Request $request)
    {
        UiNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
