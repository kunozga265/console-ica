<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Event;
use App\Models\EventResponse;
use Illuminate\Http\Request;

/**
 * "I'm attending" on an event (shared with the website's event_responses).
 * As on the website, counts are only shown to people who manage events.
 */
class EventResponseController extends Controller
{
    public function show(Request $request, Event $event)
    {
        return $this->state($event, (new WebAppController())->getAuthUser($request));
    }

    /** attending: true (attending), false (not attending) or null (clear). */
    public function store(Request $request, Event $event)
    {
        $attending = $request->validate(['attending' => ['present', 'nullable', 'boolean']])['attending'];
        $user = $request->user();

        if ($attending === null) {
            EventResponse::where(['event_id' => $event->id, 'user_id' => $user->id])->delete();
        } else {
            EventResponse::updateOrCreate(['event_id' => $event->id, 'user_id' => $user->id], ['attending' => (bool) $attending]);
        }

        return $this->state($event, $user);
    }

    private function state(Event $event, $user)
    {
        $mine = $user ? $event->responses()->where('user_id', $user->id)->value('attending') : null;
        $canSee = $user && $user->hasScope('events');

        return response()->json([
            'attending'           => $mine === null ? null : (bool) $mine,
            'attending_count'     => $canSee ? $event->responses()->where('attending', true)->count() : null,
            'not_attending_count' => $canSee ? $event->responses()->where('attending', false)->count() : null,
        ]);
    }
}
