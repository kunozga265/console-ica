<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\UsageController;
use App\Http\Resources\UI\EventResource;
use App\Http\Resources\UI\PrayerResource;
use App\Http\Resources\UI\SermonResource;
use App\Models\Event;
use App\Models\Member;
use App\Support\Birthdays;
use App\Support\SermonBody;
use App\Models\Page;
use App\Models\Prayer;
use App\Models\Sermon;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const LIMIT = 12;

    public function __invoke(Request $request)
    {
        $now = Carbon::now()->getTimestamp();

        // Only the featured (latest) sermon shows its body, so the rest leave it out.
        $sermons = Sermon::with(['author', 'series'])
            ->select(['id', 'slug', 'title', 'subtitle', 'video_url', 'author_id', 'series_id', 'published_at', 'created_at'])
            ->where('published_at', '<=', $now)
            ->orderByRaw('published_at DESC, created_at DESC')
            ->limit(self::LIMIT)
            ->get();
        if ($featured = $sermons->first()) {
            $full = Sermon::whereKey($featured->id)->first(['id', 'body', 'published_at', 'fills_revealed_at']);
            // Keep fill-in-the-blank answers hidden in the excerpt until they're revealed.
            $featured->setAttribute('body', SermonBody::concealFills($full->body, $full->fillsRevealAt() <= $now));
        }

        // Most recent first, up to and including today's.
        $prayerPoints = Prayer::where('date', '<', Carbon::tomorrow()->getTimestamp())
            ->orderByRaw('date DESC, created_at DESC')
            ->limit(self::LIMIT)
            ->get();

        $events = Event::where('end_date', '>=', $now)
            ->orderBy('start_date')
            ->limit(self::LIMIT)
            ->get();

        // Same usage tracking as the console home page (AppController::home).
        (new UsageController())->record($request);

        return Inertia::render('UI/Dashboard', [
            'sermons'       => SermonResource::collection($sermons)->resolve(),
            'prayerPoints'  => PrayerResource::collection($prayerPoints)->resolve(),
            'events'        => EventResource::collection($events)->resolve(),
            'announcements' => $this->announcements(),
            'birthdays'     => $this->birthdaysThisWeek(),
        ]);
    }

    /**
     * The "announcements" page is a single rich-text body with an on/off
     * switch (edited in the console), not a list.
     */
    private function announcements(): ?array
    {
        $contents = json_decode(Page::where('name', 'announcements')->value('contents') ?? '', true);

        if (empty($contents['activate']) || blank($contents['body'] ?? null)) {
            return null;
        }

        return ['body' => $contents['body']];
    }

    /**
     * Members whose birthday falls in the current Mon–Sun week, in day order.
     * Matched in PHP rather than with FROM_UNIXTIME, so the comparison uses the
     * app timezone instead of MySQL's session timezone (dates of birth are
     * stored as local midnight, which is the previous day in UTC).
     */
    private function birthdaysThisWeek(): array
    {
        // Shared with the app (API 1.4 dashboard); the web uses milliseconds.
        return array_map(fn ($b) => ['date' => $b['date'] * 1000] + $b, Birthdays::thisWeek());
    }
}
