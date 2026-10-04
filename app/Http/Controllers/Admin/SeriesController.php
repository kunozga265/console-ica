<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SeriesController extends Controller
{
    /** A series counts as ongoing while its latest message is under 6 weeks old. */
    private const ONGOING_WEEKS = 6;

    public function index(Request $request)
    {
        $status = $request->query('status');
        $published = fn ($q) => $q->where('published_at', '<=', now()->getTimestamp());
        $ongoingSince = now()->subWeeks(self::ONGOING_WEEKS)->getTimestamp();

        $series = Series::query()
            ->withCount(['sermons' => $published])
            ->withMin(['sermons as first_published' => $published], 'published_at')
            ->withMax(['sermons as last_published' => $published], 'published_at')
            ->with(['sermons' => fn ($q) => $published($q)->with('author:id,name,suffix')->select('id', 'series_id', 'author_id')])
            ->orderByDesc('last_published')->orderByDesc('created_at')
            ->get()
            ->map(function (Series $s) use ($ongoingSince) {
                // The minister who preached most of the series.
                $lead = $s->sermons->groupBy('author_id')->sortByDesc(fn ($g) => $g->count())->first()?->first()?->author;

                return [
                    'id'          => $s->id,
                    'title'       => $s->title,
                    'description' => $s->description,
                    'count'       => intval($s->sermons_count),
                    'first'       => $s->first_published ? $s->first_published * 1000 : null,
                    'last'        => $s->last_published ? $s->last_published * 1000 : null,
                    'lead'        => $lead ? trim($lead->suffix . ' ' . $lead->name) : null,
                    'status'      => ! $s->sermons_count ? 'empty' : ($s->last_published >= $ongoingSince ? 'ongoing' : 'completed'),
                ];
            })
            ->when(in_array($status, ['ongoing', 'completed', 'empty']), fn ($c) => $c->where('status', $status))
            ->values();

        return Inertia::render('Admin/Series', ['series' => $series, 'status' => $status]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        Series::create($v + ['slug' => Str::slug($v['title']) . date('-Y-m-d')]);

        return back()->with('success', 'Series created');
    }

    public function update(Request $request, Series $series)
    {
        $series->update($this->validated($request));

        return back()->with('success', 'Series saved');
    }

    public function destroy(Series $series)
    {
        $series->delete();

        return back()->with('success', 'Series deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
