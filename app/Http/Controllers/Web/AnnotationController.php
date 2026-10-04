<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Highlight;
use App\Models\Note;
use App\Models\Sermon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnotationController extends Controller
{
    /**
     * Highlight/bookmark/note creation and deletion, always scoped to the
     * current user. The legacy sync mechanism this replaces (AppController
     * ::syncData / HighlightController::store on the versioned API) does
     * its highlight dedupe/delete lookups without a user_id filter, so one
     * user's sync could touch another user's identical (sermon_id,
     * highlight_id) row — fixed here at the source.
     */
    public function storeHighlight(Request $request, Sermon $sermon)
    {
        $validated = $request->validate([
            "highlightId" => ["required", "integer"],
        ]);

        Highlight::firstOrCreate([
            "sermon_id" => $sermon->id,
            "user_id" => Auth::id(),
            "highlight_id" => $validated["highlightId"],
        ], [
            "date" => now()->getTimestamp(),
        ]);

        return back()->with("success", "Highlighted!");
    }

    public function destroyHighlight(Highlight $highlight)
    {
        abort_unless($highlight->user_id === Auth::id(), 403);
        $highlight->delete();

        return back()->with("success", "Highlight removed!");
    }

    public function storeBookmark(Request $request, Sermon $sermon)
    {
        $validated = $request->validate([
            "captionId" => ["required", "integer"],
            "caption" => ["required", "string"],
            "comment" => ["nullable", "string"],
        ]);

        Bookmark::updateOrCreate(
            [
                "sermon_id" => $sermon->id,
                "user_id" => Auth::id(),
                "caption_id" => $validated["captionId"],
            ],
            [
                "caption" => $validated["caption"],
                "comment" => $validated["comment"] ?? null,
                "date" => now()->getTimestamp(),
            ]
        );

        return back()->with("success", "Bookmarked!");
    }

    public function destroyBookmark(Bookmark $bookmark)
    {
        abort_unless($bookmark->user_id === Auth::id(), 403);
        $bookmark->delete();

        return back()->with("success", "Bookmark removed!");
    }

    public function storeNote(Request $request, Sermon $sermon)
    {
        $validated = $request->validate([
            "body" => ["required", "string"],
        ]);

        Note::updateOrCreate(
            [
                "sermon_id" => $sermon->id,
                "user_id" => Auth::id(),
            ],
            [
                "body" => $validated["body"],
                "date" => now()->getTimestamp(),
            ]
        );

        return back()->with("success", "Note saved!");
    }

    public function destroyNote(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        $note->delete();

        return back()->with("success", "Note removed!");
    }
}
