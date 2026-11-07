<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventResponse;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Console\Command;

/** Morning reminder (07:00 church time) for everyone who said they're attending an event today. */
class RemindEventAttendees extends Command
{
    protected $signature = 'events:remind {--date= : Act as if today were this date (Y-m-d)}';

    protected $description = "Remind attendees of today's events";

    public function handle(): int
    {
        $tz = config('app.timezone');
        $day = $this->option('date') ? Carbon::createFromFormat('Y-m-d', $this->option('date'), $tz)->startOfDay() : Carbon::today($tz);

        // Events stored as UTC midnight fall two hours into the church day.
        $events = Event::where('start_date', '>=', $day->getTimestamp())
            ->where('start_date', '<', $day->copy()->addDay()->getTimestamp())
            ->get();

        $sent = 0;
        foreach ($events as $event) {
            $userIds = EventResponse::where('event_id', $event->id)->where('attending', true)->pluck('user_id');
            $where = collect([$event->time, $event->venue])->filter()->implode(', ');
            Notify::users($userIds, "Today: {$event->title}", $where ? "See you at {$where}." : 'See you there!', 'event', (string) $event->id);
            $sent += $userIds->count();
        }

        $this->info("Reminded {$sent} attendee(s) of {$events->count()} event(s).");

        return self::SUCCESS;
    }
}
