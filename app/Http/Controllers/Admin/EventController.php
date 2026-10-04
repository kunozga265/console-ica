<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresUploads;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Ministry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    use StoresUploads;

    public function index(Request $request)
    {
        $when = in_array($request->query('when'), ['past', 'all']) ? $request->query('when') : 'upcoming';
        $now = now()->getTimestamp();
        $tz = config('app.timezone');

        $events = Event::with('ministry')
            ->withCount([
                'responses as attending_count'     => fn ($q) => $q->where('attending', true),
                'responses as not_attending_count' => fn ($q) => $q->where('attending', false),
            ])
            ->when($when === 'upcoming', fn ($q) => $q->where('end_date', '>=', $now)->orderBy('start_date'))
            ->when($when === 'past', fn ($q) => $q->where('end_date', '<', $now)->orderByDesc('start_date'))
            ->when($when === 'all', fn ($q) => $q->orderByDesc('start_date'))
            ->limit(100)
            ->get();

        // A few attendee avatars per event for the "who's coming" stack.
        $attendees = EventResponse::with('user.member:id,first_name,last_name,avatar')
            ->whereIn('event_id', $events->pluck('id'))->where('attending', true)->latest()->get()
            ->groupBy('event_id')->map(fn ($g) => $g->take(4)->map(fn ($r) => [
                'id'     => $r->user_id,
                'name'   => $r->user?->member?->fullName() ?? $r->user?->fullName(),
                'avatar' => $r->user?->member?->avatar,
            ])->values());

        return Inertia::render('Admin/Events', [
            'events' => $events->map(fn (Event $e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'venue'        => $e->venue,
                'time'         => $e->time,
                'image'        => $e->image ?: null,
                'body'         => $e->body,
                'ministryId'   => $e->ministry_id,
                'ministry'     => $e->ministry?->name,
                'start'        => $e->start_date * 1000,
                'end'          => Event::lastDayTimestamp($e) * 1000,
                'startDate'    => Carbon::createFromTimestamp($e->start_date, 'UTC')->format('Y-m-d'),
                'endDate'      => Carbon::createFromTimestamp(Event::lastDayTimestamp($e), 'UTC')->format('Y-m-d'),
                'attending'    => intval($e->attending_count),
                'notAttending' => intval($e->not_attending_count),
                'attendees'    => $attendees[$e->id] ?? [],
            ]),
            'when'       => $when,
            'ministries' => Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        Event::create($this->attributes($v) + [
            'slug'  => Str::slug($v['title']) . date('-Y-m-d'),
            'image' => $this->storeUpload($request, 'image', 'images/events') ?? '',
        ]);

        return back()->with('success', 'Event created');
    }

    public function update(Request $request, Event $event)
    {
        $event->update($this->attributes($this->validated($request)) + [
            'image' => $this->storeUpload($request, 'image', 'images/events') ?? $event->image,
        ]);

        return back()->with('success', 'Event saved');
    }

    public function destroy(Event $event)
    {
        EventResponse::where('event_id', $event->id)->delete();
        $event->delete();

        return back()->with('success', 'Event deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'      => ['required', 'string', 'max:191'],
            'venue'      => ['nullable', 'string', 'max:191'],
            'time'       => ['nullable', 'string', 'max:191'],
            'ministryId' => ['required', 'integer', 'exists:ministries,id'],
            'startDate'  => ['required', 'date_format:Y-m-d'],
            'endDate'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'body'       => ['nullable', 'string'],
            'image'      => ['nullable', 'image', 'max:6144'],
        ]);
    }

    /**
     * Existing events store dates as midnight UTC; end_date is the midnight
     * after the last day (exclusive), which keeps an event "upcoming" all
     * through its final day.
     */
    private function attributes(array $v): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $v['startDate'], 'UTC')->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $v['endDate'] ?? $v['startDate'], 'UTC')->startOfDay()->addDay();

        return [
            'title'       => $v['title'],
            'venue'       => $v['venue'] ?? null,
            'time'        => $v['time'] ?? null,
            'ministry_id' => $v['ministryId'],
            'start_date'  => $start->getTimestamp(),
            'end_date'    => $end->getTimestamp(),
            'duration'    => (int) $start->diffInDays($end),
            'body'        => $v['body'] ?? null,
        ];
    }
}
