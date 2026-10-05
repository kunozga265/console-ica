<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Resources\UI\SermonResource;
use App\Models\Attendance;
use App\Models\Bookmark;
use App\Models\Note;
use App\Models\Sermon;
use App\Models\SermonSave;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The signed-in user's own page: notes, saved things and attendance history. */
class ProfileController extends Controller
{
    private const SERMON_COLUMNS = ['id', 'slug', 'title', 'subtitle', 'video_url', 'author_id', 'series_id', 'ministry_id', 'published_at', 'created_at'];

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $member = $user->member?->loadMissing(['cell', 'leadershipCell']);

        $sermonsFor = function (string $kind) use ($user) {
            $ids = SermonSave::where('user_id', $user->id)->where('kind', $kind)->latest()->pluck('sermon_id');
            $sermons = Sermon::with(['author', 'series'])->select(self::SERMON_COLUMNS)->whereKey($ids)->get()
                ->sortBy(fn ($s) => $ids->search($s->id))->values();

            return SermonResource::collection($sermons)->resolve();
        };

        $titles = fn ($ids) => Sermon::withTrashed()->whereKey($ids)->pluck('title', 'id');

        $notes = Note::where('user_id', $user->id)->orderByDesc('updated_at')->get();
        $noteTitles = $titles($notes->pluck('sermon_id'));

        $bookmarks = Bookmark::where('user_id', $user->id)->orderByDesc('date')->get();
        $bookmarkTitles = $titles($bookmarks->pluck('sermon_id'));

        return Inertia::render('UI/Profile', [
            'profile' => [
                'name'           => $user->fullName(),
                'email'          => $user->email,
                'avatar'         => $member?->avatar ?? $user->avatar,
                'memberCode'     => $member?->code,
                'cell'           => $member?->cell ? ['code' => $member->cell->code, 'name' => $member->cell->name] : null,
                'leadershipCell' => $member?->leadershipCell ? ['code' => $member->leadershipCell->code, 'name' => $member->leadershipCell->name] : null,
            ],
            'notes' => $notes->map(fn (Note $n) => [
                'id'          => $n->id,
                'sermonId'    => intval($n->sermon_id),
                'sermonTitle' => $noteTitles[$n->sermon_id] ?? 'Sermon',
                'body'        => $n->body,
                'date'        => ($n->updated_at ?? $n->created_at)?->getTimestamp() * 1000,
            ])->values(),
            'saved'     => $sermonsFor('saved'),
            'favorites' => $sermonsFor('favorite'),
            'bookmarks' => $bookmarks->map(fn (Bookmark $b) => [
                'id'          => $b->id,
                'sermonId'    => intval($b->sermon_id),
                'sermonTitle' => $bookmarkTitles[$b->sermon_id] ?? 'Sermon',
                'caption'     => $b->caption,
                'date'        => intval($b->date) * 1000,
            ])->values(),
            'attendance' => $member ? $this->attendance($member->id) : null,
        ]);
    }

    /** Service check-ins and cell meetings attended, merged into one timeline. */
    private function attendance(int $memberId): array
    {
        $records = Attendance::with(['register.ministry', 'meeting.cell'])->where('member_id', $memberId)->get();

        $items = $records->map(function (Attendance $a) {
            if ($a->register_id && $a->register) {
                return [
                    'kind'   => 'service',
                    'title'  => $a->register->name,
                    'detail' => $a->register->ministry?->name,
                    'date'   => intval($a->register->date) * 1000,
                ];
            }
            if ($a->meeting_id && $a->meeting) {
                return [
                    'kind'   => 'cell',
                    'title'  => $a->meeting->cell?->name ?? 'Cell meeting',
                    'detail' => $a->meeting->venue,
                    'date'   => intval($a->meeting->date) * 1000,
                ];
            }

            return null;
        })->filter()->sortByDesc('date')->values();

        $thisYear = now()->startOfYear()->getTimestamp() * 1000;

        return [
            'items' => $items->take(200)->values(),
            'stats' => [
                'servicesThisYear' => $items->where('kind', 'service')->where('date', '>=', $thisYear)->count(),
                'cellThisYear'     => $items->where('kind', 'cell')->where('date', '>=', $thisYear)->count(),
                'total'            => $items->count(),
            ],
        ];
    }
}
