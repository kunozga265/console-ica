<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Ministry;
use App\Models\Series;
use App\Models\Sermon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SermonController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'author', 'series', 'ministry', 'status']), 'filled');
        $now = now()->getTimestamp();

        $page = Sermon::query()
            ->select(['id', 'title', 'subtitle', 'video_url', 'author_id', 'series_id', 'ministry_id', 'published_at', 'deleted_at'])
            ->with(['author', 'series', 'ministry'])
            ->withSum('viewRecords', 'count')
            ->when(($filters['status'] ?? null) === 'deleted', fn (Builder $q) => $q->onlyTrashed())
            ->when(($filters['status'] ?? null) === 'scheduled', fn (Builder $q) => $q->where('published_at', '>', $now))
            ->when(($filters['status'] ?? null) === 'published', fn (Builder $q) => $q->where('published_at', '<=', $now))
            ->when($filters['search'] ?? null, fn (Builder $q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('subtitle', 'like', "%{$s}%")))
            ->when($filters['author'] ?? null, fn (Builder $q, $id) => $q->where('author_id', $id))
            ->when($filters['series'] ?? null, fn (Builder $q, $id) => $q->where('series_id', $id))
            ->when($filters['ministry'] ?? null, fn (Builder $q, $id) => $q->where('ministry_id', $id))
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Admin/Sermons/Index', [
            'sermons' => collect($page->items())->map(fn (Sermon $s) => [
                'id'       => $s->id,
                'title'    => $s->title,
                'subtitle' => $s->subtitle,
                'author'   => $s->author ? ['id' => $s->author->id, 'name' => trim($s->author->suffix . ' ' . $s->author->name), 'avatar' => $s->author->avatar] : null,
                'series'   => $s->series?->title,
                'ministry' => $s->ministry?->name,
                'date'     => $s->published_at * 1000,
                'hasVideo' => (bool) $s->video_url,
                'views'    => intval($s->view_records_sum_count),
                'status'   => $s->trashed() ? 'deleted' : ($s->published_at > $now ? 'scheduled' : 'published'),
            ]),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem()],
            'filters' => (object) $filters,
            ...$this->lookups(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Sermons/Form', ['sermon' => null, ...$this->lookups()]);
    }

    public function edit(Sermon $sermon)
    {
        return Inertia::render('Admin/Sermons/Form', [
            'sermon' => [
                'id'          => $sermon->id,
                'title'       => $sermon->title,
                'subtitle'    => $sermon->subtitle,
                'body'        => $sermon->body,
                'authorId'    => $sermon->author_id,
                'seriesId'    => $sermon->series_id,
                'ministryId'  => $sermon->ministry_id,
                'videoUrl'    => $sermon->video_url,
                'publishedAt' => Carbon::createFromTimestamp($sermon->published_at, config('app.timezone'))->format('Y-m-d\TH:i'),
                'deleted'     => $sermon->trashed(),
            ],
            ...$this->lookups(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        $sermon = Sermon::create($this->attributes($v) + ['slug' => Str::slug($v['title']) . date('-Y-m-d')]);

        return redirect()->route('admin.sermons.edit', $sermon)->with('success', 'Sermon created');
    }

    public function update(Request $request, Sermon $sermon)
    {
        $sermon->update($this->attributes($this->validated($request)));

        return back()->with('success', 'Sermon saved');
    }

    public function destroy(Sermon $sermon)
    {
        $sermon->delete();

        return back()->with('success', 'Sermon deleted');
    }

    public function restore(Sermon $sermon)
    {
        $sermon->restore();

        return back()->with('success', 'Sermon restored');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:191'],
            'subtitle'    => ['nullable', 'string', 'max:191'],
            'body'        => ['required', 'string'],
            'authorId'    => ['required', 'integer', 'exists:authors,id'],
            'seriesId'    => ['nullable', 'integer', 'exists:series,id'],
            'ministryId'  => ['required', 'integer', 'exists:ministries,id'],
            'videoUrl'    => ['nullable', 'url', 'max:191'],
            'publishedAt' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
    }

    private function attributes(array $v): array
    {
        return [
            'title'        => $v['title'],
            'subtitle'     => $v['subtitle'] ?? null,
            'body'         => $v['body'],
            'author_id'    => $v['authorId'],
            'series_id'    => $v['seriesId'] ?? null,
            'ministry_id'  => $v['ministryId'],
            'video_url'    => $v['videoUrl'] ?? null,
            'published_at' => Carbon::createFromFormat('Y-m-d\TH:i', $v['publishedAt'], config('app.timezone'))->getTimestamp(),
        ];
    }

    private function lookups(): array
    {
        return [
            'authors'    => Author::orderBy('name')->get(['id', 'name', 'suffix'])->map(fn ($a) => ['id' => $a->id, 'name' => trim($a->suffix . ' ' . $a->name)]),
            'series'     => Series::orderBy('title')->get(['id', 'title']),
            'ministries' => Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get(['id', 'name']),
        ];
    }
}
