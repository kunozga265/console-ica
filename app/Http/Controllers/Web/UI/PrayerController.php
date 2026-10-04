<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Prayer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrayerController extends Controller
{
    private const PER_PAGE = 24;

    public function index(Request $request)
    {
        $page = max(1, intval($request->query('page', 1)));
        $query = $this->upToToday(Prayer::query())->orderByRaw('date DESC, created_at DESC');
        $total = (clone $query)->count();

        // Same "load more" contract as the sermon list: partial reloads get one
        // page to merge, a full load (e.g. refresh) gets pages 1…$page.
        $partial = $request->header('X-Inertia-Partial-Data') !== null;
        $prayers = $partial
            ? $query->forPage($page, self::PER_PAGE)->get()
            : $query->limit($page * self::PER_PAGE)->get();

        return Inertia::render('UI/Prayer', [
            'prayerPoints' => Inertia::merge(fn () => $this->present($prayers, $request)),
            'pagination'   => ['page' => $page, 'total' => $total, 'hasMore' => $page * self::PER_PAGE < $total],
        ]);
    }

    public function show(Request $request, Prayer $prayer)
    {
        abort_if($prayer->date >= Carbon::tomorrow()->getTimestamp(), 404);

        $around = fn ($op, $dir) => $this->upToToday(Prayer::query())
            ->where('date', $op, $prayer->date)->orderBy('date', $dir)->first(['id', 'title', 'date']);

        return Inertia::render('UI/PrayerShow', [
            'prayer'   => $this->present(collect([$prayer]), $request, withBody: true)[0],
            'previous' => $this->brief($around('<', 'desc')),
            'next'     => $this->brief($around('>', 'asc')),
        ]);
    }

    private function upToToday($query)
    {
        return $query->where('date', '<', Carbon::tomorrow()->getTimestamp());
    }

    private function brief(?Prayer $prayer): ?array
    {
        return $prayer ? ['id' => $prayer->id, 'title' => $prayer->title, 'date' => intval($prayer->date) * 1000] : null;
    }

    /** Prayer points with how many are praying and whether the user is. */
    private function present($prayers, Request $request, bool $withBody = false): array
    {
        $prayers = Prayer::whereKey($prayers->pluck('id'))
            ->withCount('prayingUsers')
            ->when($request->user(), fn ($q, $user) => $q->withExists(['prayingUsers as praying' => fn ($q) => $q->where('users.id', $user->id)]))
            ->get()
            ->sortBy(fn ($p) => $prayers->search(fn ($x) => $x->id === $p->id))
            ->values();

        return $prayers->map(fn (Prayer $p) => array_filter([
            'id'           => intval($p->id),
            'date'         => intval($p->date) * 1000,
            'title'        => $p->title,
            'verses'       => $p->verses,
            'body'         => $withBody ? $p->body : null,
            'prayingCount' => intval($p->praying_users_count),
            'praying'      => (bool) ($p->praying ?? false),
        ], fn ($v, $k) => $v !== null || $k === 'verses', ARRAY_FILTER_USE_BOTH))->all();
    }
}
