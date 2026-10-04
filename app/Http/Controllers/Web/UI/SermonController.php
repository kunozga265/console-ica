<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Resources\UI\AuthorResource;
use App\Http\Resources\UI\SeriesResource;
use App\Http\Resources\UI\SermonResource;
use App\Models\Author;
use App\Models\Ministry;
use App\Models\Series;
use App\Models\Sermon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SermonController extends Controller
{
    private const PER_PAGE = 18;

    /** Everything but the body, which list cards never show. */
    private const LIST_COLUMNS = ['id', 'title', 'subtitle', 'video_url', 'author_id', 'series_id', 'ministry_id', 'published_at', 'created_at'];

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'author', 'series', 'ministry', 'from', 'to']), 'filled');
        // Date range (Y-m-d, inclusive) in the app timezone; unparseable values are dropped.
        $tz = config('app.timezone');
        $from = $this->parseDate($filters['from'] ?? null, $tz)?->startOfDay();
        $to = $this->parseDate($filters['to'] ?? null, $tz)?->endOfDay();
        if (! $from) unset($filters['from']);
        if (! $to) unset($filters['to']);
        $page = max(1, intval($request->query('page', 1)));

        $query = $this->published(Sermon::query())
            ->select(self::LIST_COLUMNS)
            ->with(['author', 'series'])
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(
                fn (Builder $q) => $q->where('title', 'like', "%{$search}%")->orWhere('subtitle', 'like', "%{$search}%")
            ))
            ->when($filters['author'] ?? null, fn (Builder $q, $author) => $q->where('author_id', $author))
            ->when($filters['series'] ?? null, fn (Builder $q, $series) => $q->where('series_id', $series))
            ->when($filters['ministry'] ?? null, fn (Builder $q, $ministry) => $q->where('ministry_id', $ministry))
            ->when($from, fn (Builder $q) => $q->where('published_at', '>=', $from->getTimestamp()))
            ->when($to, fn (Builder $q) => $q->where('published_at', '<=', $to->getTimestamp()))
            ->orderByRaw('published_at DESC, created_at DESC');

        $total = (clone $query)->count();

        // "Load more" asks for one page at a time (a partial reload whose
        // result the client merges onto the list). A full page load, e.g. a
        // refresh after loading more, returns pages 1…$page in one go instead.
        $partial = $request->header('X-Inertia-Partial-Data') !== null;
        $sermons = $partial
            ? $query->forPage($page, self::PER_PAGE)->get()
            : $query->limit($page * self::PER_PAGE)->get();

        return Inertia::render('UI/Sermons/Index', [
            'sermons'    => Inertia::merge(fn () => SermonResource::collection($sermons)->resolve()),
            'pagination' => ['page' => $page, 'total' => $total, 'hasMore' => $page * self::PER_PAGE < $total],
            'filters'    => (object) $filters,
            'series'     => fn () => $this->series(),
            'ministers'  => fn () => $this->ministers(),
            'ministries' => fn () => Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Sermon $sermon)
    {
        abort_if($sermon->published_at > now()->getTimestamp(), 404);

        $sermon->load(['author', 'series', 'ministry']);
        $user = $request->user();
        $isMember = $user?->member_id !== null;

        $data = (new SermonResource($sermon))->resolve();
        unset($data['body']); // the reader renders renderedBody instead

        $list = fn () => $this->published(Sermon::query())->select(self::LIST_COLUMNS)->with(['author', 'series']);

        // Chronological neighbours across all published sermons (ties broken by id).
        $previous = $list()
            ->where(fn (Builder $q) => $q->where('published_at', '<', $sermon->published_at)
                ->orWhere(fn (Builder $q) => $q->where('published_at', $sermon->published_at)->where('id', '<', $sermon->id)))
            ->orderByDesc('published_at')->orderByDesc('id')->first();
        $next = $list()
            ->where(fn (Builder $q) => $q->where('published_at', '>', $sermon->published_at)
                ->orWhere(fn (Builder $q) => $q->where('published_at', $sermon->published_at)->where('id', '>', $sermon->id)))
            ->orderBy('published_at')->orderBy('id')->first();

        return Inertia::render('UI/Sermons/Show', [
            'sermon'       => $data,
            // Body split into numbered sentence spans; highlights and bookmarks
            // store a span number — the scheme the mobile app and portal use.
            'renderedBody' => $sermon->refactorBody(),
            'highlights'   => $isMember
                ? $sermon->highlights()->where('user_id', $user->id)->get(['id', 'highlight_id'])
                    ->map(fn ($h) => ['id' => $h->id, 'spanId' => intval($h->highlight_id)])->values()
                : [],
            'bookmarks'    => $isMember
                ? $sermon->bookmarks()->where('user_id', $user->id)->get(['id', 'caption_id'])
                    ->map(fn ($b) => ['id' => $b->id, 'spanId' => intval($b->caption_id)])->values()
                : [],
            'note'         => $isMember ? $sermon->notes()->where('user_id', $user->id)->first(['id', 'body']) : null,
            'canAnnotate'  => $isMember,
            // The rest of this sermon's series, in preaching order.
            'inSeries'     => $sermon->series_id
                ? SermonResource::collection($list()->where('series_id', $sermon->series_id)->whereKeyNot($sermon->id)
                    ->orderBy('published_at')->orderBy('id')->get())->resolve()
                : [],
            'previous'     => $previous ? (new SermonResource($previous))->resolve() : null,
            'next'         => $next ? (new SermonResource($next))->resolve() : null,
        ]);
    }

    private function parseDate(?string $value, string $tz): ?Carbon
    {
        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    private function published(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now()->getTimestamp());
    }

    /** Series with at least one published sermon, most recently active first. */
    private function series(): array
    {
        $published = fn ($q) => $this->published($q);

        return SeriesResource::collection(
            Series::whereHas('sermons', $published)
                ->withCount(['sermons' => $published])
                ->withMin(['sermons as first_published' => $published], 'published_at')
                ->withMax(['sermons as last_published' => $published], 'published_at')
                ->orderByDesc('last_published')
                ->get()
        )->resolve();
    }

    /** Ministers with at least one published sermon, most prolific first. */
    private function ministers(): array
    {
        $published = fn ($q) => $this->published($q);

        return AuthorResource::collection(
            Author::whereHas('sermons', $published)
                ->withCount(['sermons' => $published])
                ->orderByDesc('sermons_count')
                ->orderBy('name')
                ->get()
        )->resolve();
    }
}
