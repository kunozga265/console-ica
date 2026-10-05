<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\UsageController;
use App\Http\Resources\UI\EventResource;
use App\Http\Resources\UI\PrayerResource;
use App\Http\Resources\UI\SermonResource;
use App\Models\Event;
use App\Models\Member;
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
        $sermons->first()?->setAttribute('body', Sermon::whereKey($sermons->first()->id)->value('body'));

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
        $tz = config('app.timezone');
        $week = [];
        foreach (CarbonPeriod::create(Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()) as $day) {
            $week[$day->format('m-d')] = $day->copy()->startOfDay();
        }

        return Member::with('cell')
            ->whereNotNull('date_of_birth')
            ->get(['id', 'first_name', 'last_name', 'avatar', 'date_of_birth', 'cell_id'])
            ->map(function (Member $member) use ($week, $tz) {
                $key = Carbon::createFromTimestamp($member->date_of_birth, $tz)->format('m-d');

                return isset($week[$key]) ? [
                    'id'     => intval($member->id),
                    'name'   => $member->fullName(),
                    'avatar' => $member->avatar,
                    'cell'   => $member->cell?->name,
                    'date'   => $week[$key]->getTimestamp() * 1000,
                ] : null;
            })
            ->filter()
            ->sortBy('date')
            ->values()
            ->all();
    }
}
