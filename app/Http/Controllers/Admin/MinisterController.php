<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresUploads;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Sermon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** Ministers are the `authors` of sermons. */
class MinisterController extends Controller
{
    use StoresUploads;

    public function index(Request $request)
    {
        $type = $request->query('type'); // 'pastors' | 'guests'
        $published = fn ($q) => $q->where('published_at', '<=', now()->getTimestamp());

        $authors = Author::query()
            ->withCount(['sermons' => $published])
            ->when($type === 'pastors', fn ($q) => $q->where('ica_pastor', true))
            ->when($type === 'guests', fn ($q) => $q->where('ica_pastor', false))
            ->orderByDesc('sermons_count')->orderBy('name')
            ->get();

        $ids = $authors->pluck('id');
        $seriesCounts = Sermon::where('published_at', '<=', now()->getTimestamp())->whereIn('author_id', $ids)->whereNotNull('series_id')
            ->selectRaw('author_id, COUNT(DISTINCT series_id) n')->groupBy('author_id')->pluck('n', 'author_id');
        $latest = Sermon::where('published_at', '<=', now()->getTimestamp())->whereIn('author_id', $ids)
            ->orderByDesc('published_at')->get(['id', 'author_id', 'title'])->unique('author_id')->keyBy('author_id');

        return Inertia::render('Admin/Ministers', [
            'ministers' => $authors->map(fn (Author $a) => [
                'id'        => $a->id,
                'name'      => $a->name,
                'suffix'    => trim((string) $a->suffix),
                'title'     => $a->title,
                'biography' => $a->biography,
                'avatar'    => $a->avatar,
                'icaPastor' => (bool) $a->ica_pastor,
                'sermons'   => intval($a->sermons_count),
                'series'    => intval($seriesCounts[$a->id] ?? 0),
                'latest'    => $latest[$a->id] ?? null,
            ]),
            'type' => $type,
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        Author::create($this->attributes($v) + [
            'slug'   => Str::slug($v['name']) . date('-Y-m-d'),
            'avatar' => $this->storeUpload($request, 'avatar', 'images/authors') ?? 'images/avatar.png',
        ]);

        return back()->with('success', 'Minister added');
    }

    public function update(Request $request, Author $author)
    {
        $author->update($this->attributes($this->validated($request)) + [
            'avatar' => $this->storeUpload($request, 'avatar', 'images/authors') ?? $author->avatar,
        ]);

        return back()->with('success', 'Minister saved');
    }

    /** Only ministers without sermons can be removed (sermons need an author). */
    public function destroy(Author $author)
    {
        if (Sermon::withTrashed()->where('author_id', $author->id)->exists()) {
            return back()->withErrors(['minister' => "{$author->name} has sermons. Move them to another minister first."]);
        }

        $author->delete();

        return back()->with('success', 'Minister removed');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'      => ['required', 'string', 'max:191'],
            'suffix'    => ['nullable', 'string', 'max:191'],
            'title'     => ['required', 'string', 'max:191'],
            'biography' => ['nullable', 'string'],
            'icaPastor' => ['boolean'],
            'avatar'    => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function attributes(array $v): array
    {
        return [
            'name'       => $v['name'],
            'suffix'     => $v['suffix'] ?? null,
            'title'      => $v['title'],
            'biography'  => $v['biography'] ?? null,
            'ica_pastor' => (bool) ($v['icaPastor'] ?? false),
        ];
    }
}
