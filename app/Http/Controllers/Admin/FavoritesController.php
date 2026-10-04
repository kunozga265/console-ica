<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Highlight;
use App\Models\Sermon;
use App\Models\SermonSave;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Which sermons people favourite, save, highlight and bookmark most. */
class FavoritesController extends Controller
{
    public function __invoke()
    {
        $top = function ($query, string $column = 'sermon_id') {
            $rows = $query->select($column, DB::raw('COUNT(*) as n'))->groupBy($column)->orderByDesc('n')->limit(10)->get();
            $sermons = Sermon::withTrashed()->with('author:id,name,suffix')->whereKey($rows->pluck($column))->get(['id', 'title', 'author_id', 'published_at'])->keyBy('id');

            return $rows->map(fn ($r) => ($s = $sermons[$r->{$column}] ?? null) ? [
                'id'     => $s->id,
                'title'  => $s->title,
                'author' => trim($s->author?->suffix . ' ' . $s->author?->name),
                'date'   => $s->published_at * 1000,
                'count'  => intval($r->n),
            ] : null)->filter()->values();
        };

        return Inertia::render('Admin/Favorites', [
            'favorites'  => $top(SermonSave::where('kind', 'favorite')),
            'saved'      => $top(SermonSave::where('kind', 'saved')),
            'highlights' => $top(Highlight::query()),
            'bookmarks'  => $top(Bookmark::query()),
            'totals'     => [
                'favorites'  => SermonSave::where('kind', 'favorite')->count(),
                'saved'      => SermonSave::where('kind', 'saved')->count(),
                'highlights' => Highlight::count(),
                'bookmarks'  => Bookmark::count(),
            ],
        ]);
    }
}
