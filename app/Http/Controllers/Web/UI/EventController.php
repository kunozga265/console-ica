<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Ministry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $filters = array_filter($request->only(['ministry', 'when']), 'filled');
        $past = ($filters['when'] ?? null) === 'past';
        $now = now()->getTimestamp();
        $userId = $request->user()?->id;
        // Response counts are for admins only (the future admin dashboard).
        $isAdmin = (bool) $request->user()?->hasAnyRole(['admin', 'super']);

        $events = Event::with('ministry')
            ->when($isAdmin, fn ($q) => $q->withCount([
                'responses as attending_count'     => fn ($q) => $q->where('attending', true),
                'responses as not_attending_count' => fn ($q) => $q->where('attending', false),
            ]))
            ->when($filters['ministry'] ?? null, fn ($q, $ministry) => $q->where('ministry_id', $ministry))
            ->when($past,
                fn ($q) => $q->where('end_date', '<', $now)->orderByDesc('start_date'),
                fn ($q) => $q->where('end_date', '>=', $now)->orderBy('start_date'))
            ->limit(60)
            ->get();

        $mine = $userId
            ? EventResponse::where('user_id', $userId)->whereIn('event_id', $events->pluck('id'))->pluck('attending', 'event_id')
            : collect();

        return Inertia::render('UI/Events', [
            'events' => $events->map(fn (Event $e) => [
                'id'             => intval($e->id),
                'title'          => $e->title,
                'date'           => intval($e->start_date) * 1000,
                'endDate'        => Event::lastDayTimestamp($e) * 1000,
                'time'           => $e->time,
                'loc'            => $e->venue,
                'image'          => $e->image ?: null,
                'body'           => $e->body,
                'ministry'       => $e->ministry ? ['id' => $e->ministry->id, 'name' => $e->ministry->name] : null,
                'attendingCount'    => $isAdmin ? intval($e->attending_count) : null,
                'notAttendingCount' => $isAdmin ? intval($e->not_attending_count) : null,
                // true = attending, false = not attending, null = no answer yet
                'myResponse'     => $mine->has($e->id) ? (bool) $mine[$e->id] : null,
            ])->values(),
            'filters'    => (object) $filters,
            'ministries' => Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
