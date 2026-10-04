<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Resources\UI\AuthorResource;
use App\Models\Author;
use App\Models\Cell;
use App\Models\Prayer;
use App\Models\Sermon;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function __invoke()
    {
        $published = fn ($q) => $q->where('published_at', '<=', now()->getTimestamp());

        return Inertia::render('UI/About', [
            'stats' => [
                'sermons'      => Sermon::where('published_at', '<=', now()->getTimestamp())->count(),
                'prayerPoints' => Prayer::where('date', '<=', now()->getTimestamp())->count(),
                'cells'        => Cell::where('verified', true)->count(),
            ],
            'pastors' => AuthorResource::collection(
                Author::where('ica_pastor', true)->withCount(['sermons' => $published])->orderByDesc('sermons_count')->get()
            )->resolve(),
        ]);
    }
}
