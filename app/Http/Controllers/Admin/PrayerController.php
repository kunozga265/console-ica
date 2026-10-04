<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prayer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrayerController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $tz = config('app.timezone');

        $page = Prayer::withCount('prayingUsers')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('verses', 'like', "%{$search}%")))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        // Prayer dates are stored as midnight UTC of the calendar day (existing data convention).
        $today = Carbon::parse(Carbon::today($tz)->format('Y-m-d'), 'UTC')->getTimestamp();

        return Inertia::render('Admin/Prayer', [
            'prayers' => collect($page->items())->map(fn (Prayer $p) => [
                'id'      => $p->id,
                'title'   => $p->title,
                'verses'  => $p->verses,
                'body'    => $p->body,
                'date'    => Carbon::createFromTimestamp($p->date, 'UTC')->format('Y-m-d'),
                'praying' => intval($p->praying_users_count),
                'state'   => $p->date >= $today + 86400 ? 'scheduled' : ($p->date >= $today ? 'today' : 'past'),
            ]),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total()],
            'search'  => $search,
            'stats'   => [
                'total'     => Prayer::count(),
                'scheduled' => Prayer::where('date', '>=', $today + 86400)->count(),
                'praying'   => \DB::table('prayer_user')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Prayer::create($this->attributes($this->validated($request)));

        return back()->with('success', 'Prayer point added');
    }

    public function update(Request $request, Prayer $prayer)
    {
        $prayer->update($this->attributes($this->validated($request)));

        return back()->with('success', 'Prayer point saved');
    }

    public function destroy(Prayer $prayer)
    {
        $prayer->prayingUsers()->detach();
        $prayer->delete();

        return back()->with('success', 'Prayer point deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'  => ['required', 'string', 'max:191'],
            'verses' => ['nullable', 'string', 'max:191'],
            'body'   => ['required', 'string'],
            'date'   => ['required', 'date_format:Y-m-d'],
        ]);
    }

    private function attributes(array $v): array
    {
        return [
            'title'  => $v['title'],
            'verses' => $v['verses'] ?? null,
            'body'   => $v['body'],
            // Midnight UTC of the calendar day, matching existing prayer points.
            'date'   => Carbon::createFromFormat('Y-m-d', $v['date'], 'UTC')->startOfDay()->getTimestamp(),
        ];
    }
}
