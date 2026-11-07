<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Prayer;
use App\Models\Sermon;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sends the broadcast for each sermon, prayer point and event once, when it
 * goes live (so scheduled sermons notify on their publish time, not when they
 * were saved). Runs every five minutes; notified_at makes it idempotent.
 */
class DispatchNotifications extends Command
{
    protected $signature = 'notifications:dispatch';

    protected $description = 'Push new sermons, today\'s prayer points and new events';

    /** Prayer points go out from this hour (church time) on their day. */
    private const PRAYER_HOUR = 6;

    public function handle(): int
    {
        $tz = config('app.timezone');
        $now = Carbon::now($tz);
        $sent = 0;

        $sermons = Sermon::with(['author', 'ministry'])
            ->whereNull('notified_at')
            ->where('published_at', '<=', $now->getTimestamp())
            ->orderBy('published_at')
            ->get();
        foreach ($sermons as $sermon) {
            if (! $this->claim($sermon)) {
                continue;
            }
            $by = $sermon->authorName() ?: ($sermon->ministry?->name ?? 'ICA');
            Notify::topic('SERMONS-MIN-'.($sermon->ministry_id ?: 1), $sermon->title, "New sermon · {$by}", 'sermon', $sermon->slug);
            $sent++;
        }

        // Today's prayer points (stored as UTC or church midnight: both fall in today's church day).
        if ($now->hour >= self::PRAYER_HOUR) {
            $today = $now->copy()->startOfDay();
            $prayers = Prayer::whereNull('notified_at')
                ->where('date', '<', $today->copy()->addDay()->getTimestamp())
                ->orderBy('date')
                ->get();
            foreach ($prayers as $prayer) {
                // Back-dated entries are just marked, not announced.
                $isToday = $prayer->date >= $today->getTimestamp();
                if (! $this->claim($prayer) || ! $isToday) {
                    continue;
                }
                Notify::topic('PRAYER', "Today's prayer points", $prayer->title, 'prayer', (string) $prayer->id, legacy: false);
                $sent++;
            }
        }

        $events = Event::whereNull('notified_at')->get();
        foreach ($events as $event) {
            $upcoming = ($event->end_date ?: $event->start_date) >= $now->getTimestamp();
            if (! $this->claim($event) || ! $upcoming) {
                continue;
            }
            $when = Carbon::createFromTimestamp($event->start_date, $tz)->format('D j M');
            Notify::topic('EVENTS', $event->title, trim("{$when}".($event->venue ? " · {$event->venue}" : '')), 'event', (string) $event->id, legacy: false);
            $sent++;
        }

        $this->info("Sent {$sent} notification(s).");

        return self::SUCCESS;
    }

    /** Marks the row notified; false if another run got there first. */
    private function claim(\Illuminate\Database\Eloquent\Model $model): bool
    {
        return $model->newQuery()->whereKey($model->getKey())->whereNull('notified_at')->update(['notified_at' => now()]) === 1;
    }
}
