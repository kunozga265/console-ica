<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Member;
use App\Models\Prayer;
use App\Models\Register;
use App\Models\Sermon;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $tz = config('app.timezone');
        $now = Carbon::now($tz);
        $monthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonth();

        $published = fn () => Sermon::where('published_at', '<=', $now->getTimestamp());

        // Members
        $members = Member::where('is_registered', true);
        $newThisMonth = (clone $members)->where('created_at', '>=', $monthStart)->count();

        // Service attendance: latest register vs the one before it.
        $recentRegisters = Register::withCount('members')->where('date', '<=', $now->getTimestamp())->orderByDesc('date')->limit(2)->get();
        $latest = $recentRegisters->get(0);
        $previous = $recentRegisters->get(1);

        // Cell offerings (money in) this month vs last month.
        $offeringsThisMonth = Transaction::where('type', 0)->where('created_at', '>=', $monthStart)->sum('amount');
        $offeringsLastMonth = Transaction::where('type', 0)->whereBetween('created_at', [$lastMonthStart, $monthStart])->sum('amount');

        return Inertia::render('Admin/Dashboard', [
            'greetingName' => $request->user()->first_name,
            'stats' => [
                'members'             => (clone $members)->count(),
                'membersNewThisMonth' => $newThisMonth,
                'visitors'            => Member::where('is_registered', false)->count(),
                'lastService'         => $latest ? ['name' => $latest->name, 'date' => $latest->date * 1000, 'present' => $latest->members_count] : null,
                'previousService'     => $previous ? ['present' => $previous->members_count] : null,
                'sermons'             => $published()->count(),
                'sermonsThisMonth'    => $published()->where('published_at', '>=', $monthStart->getTimestamp())->count(),
                'offeringsThisMonth'  => floatval($offeringsThisMonth),
                'offeringsLastMonth'  => floatval($offeringsLastMonth),
                'monthName'           => $now->format('F'),
            ],
            'weeks'    => $this->weeklyAttendance(8),
            'sermons'  => $published()->with(['author', 'series'])->withSum('viewRecords', 'count')
                ->orderByDesc('published_at')->limit(5)->get()
                ->map(fn (Sermon $s) => [
                    'id'     => $s->id,
                    'title'  => $s->title,
                    'author' => trim($s->author?->suffix . ' ' . $s->author?->name),
                    'series' => $s->series?->title,
                    'date'   => $s->published_at * 1000,
                    'views'  => intval($s->view_records_sum_count),
                ]),
            'events' => Event::with('ministry')->where('end_date', '>=', $now->getTimestamp())->orderBy('start_date')->limit(4)->get()
                ->map(fn (Event $e) => [
                    'id' => $e->id, 'title' => $e->title, 'date' => $e->start_date * 1000,
                    'time' => $e->time, 'venue' => $e->venue, 'ministry' => $e->ministry?->name,
                ]),
            'prayer'    => Prayer::where('date', '<', Carbon::tomorrow($tz)->getTimestamp())->orderByDesc('date')->first(['id', 'title', 'verses', 'date']),
            'birthdays' => $this->birthdaysThisWeek($now),
        ]);
    }

    /**
     * Attendance per week for the last $weeks weeks: people checked in to
     * service registers, and people present at cell meetings.
     */
    private function weeklyAttendance(int $weeks): array
    {
        $tz = config('app.timezone');
        $start = Carbon::now($tz)->startOfWeek()->subWeeks($weeks - 1);

        $services = Attendance::query()
            ->join('registers', 'registers.id', '=', 'attendances.register_id')
            ->where('registers.date', '>=', $start->getTimestamp())
            ->get(['registers.date as at']);
        $cells = Attendance::query()
            ->join('meetings', 'meetings.id', '=', 'attendances.meeting_id')
            ->where('meetings.date', '>=', $start->getTimestamp())
            ->get(['meetings.date as at']);

        $bucket = fn ($rows) => $rows->countBy(fn ($r) => Carbon::createFromTimestamp($r->at, $tz)->startOfWeek()->format('Y-m-d'));
        $s = $bucket($services);
        $c = $bucket($cells);

        return collect(range(0, $weeks - 1))->map(function ($i) use ($start, $s, $c) {
            $week = $start->copy()->addWeeks($i);
            $key = $week->format('Y-m-d');

            return ['label' => $week->format('j M'), 'services' => $s[$key] ?? 0, 'cells' => $c[$key] ?? 0];
        })->all();
    }

    private function birthdaysThisWeek(Carbon $now): array
    {
        $tz = config('app.timezone');
        $week = [];
        for ($d = $now->copy()->startOfWeek(); $d->lte($now->copy()->endOfWeek()); $d->addDay()) {
            $week[$d->format('m-d')] = $d->copy()->startOfDay();
        }

        return Member::whereNotNull('date_of_birth')->get(['id', 'first_name', 'last_name', 'avatar', 'date_of_birth'])
            ->map(function (Member $m) use ($week, $tz) {
                $dob = Carbon::createFromTimestamp($m->date_of_birth, $tz);
                $day = $week[$dob->format('m-d')] ?? null;

                return $day ? [
                    'id' => $m->id, 'name' => $m->fullName(), 'avatar' => $m->avatar,
                    'date' => $day->getTimestamp() * 1000, 'age' => $day->year - $dob->year,
                ] : null;
            })->filter()->sortBy('date')->values()->all();
    }
}
