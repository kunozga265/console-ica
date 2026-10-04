<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\BookmarkResource;
use App\Http\Resources\V2\HighlightResource;
use App\Http\Resources\V2\NoteResource;
use App\Models\Bookmark;
use App\Models\Highlight;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnotationController extends Controller
{
    /**
     * One canonical, user-scoped highlights/bookmarks/notes surface,
     * replacing: the legacy syncData/deleteData pair on AppController (whose
     * dedupe/delete lookups weren't scoped by user_id — one user's sync
     * could touch another user's identical sermon+highlight row), the
     * separately broken V1_2\BookmarkController::store (mismatched "text"
     * field instead of "caption"), and the unimplemented V1_2\NoteController.
     * Also fixes the gap where highlights/bookmarks/notes could previously
     * only be re-fetched by calling login/confirm again — there was no
     * dedicated GET endpoint (the dead authData() method hinted this was
     * intended but never wired up).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }

    public function storeHighlight(Request $request)
    {
        $validated = $request->validate([
            "sermonId" => ["required", "integer", "exists:sermons,id"],
            "highlightId" => ["required", "integer"],
        ]);

        $highlight = Highlight::firstOrCreate([
            "sermon_id" => $validated["sermonId"],
            "user_id" => Auth::id(),
            "highlight_id" => $validated["highlightId"],
        ], [
            "date" => now()->getTimestamp(),
        ]);

        return new HighlightResource($highlight);
    }

    public function destroyHighlight(Highlight $highlight)
    {
        abort_unless($highlight->user_id === Auth::id(), 403);
        $highlight->delete();

        return response()->json(["message" => "Highlight removed"]);
    }

    public function storeBookmark(Request $request)
    {
        $validated = $request->validate([
            "sermonId" => ["required", "integer", "exists:sermons,id"],
            "captionId" => ["required", "integer"],
            "caption" => ["required", "string"],
            "comment" => ["nullable", "string"],
        ]);

        $bookmark = Bookmark::updateOrCreate(
            [
                "sermon_id" => $validated["sermonId"],
                "user_id" => Auth::id(),
                "caption_id" => $validated["captionId"],
            ],
            [
                "caption" => $validated["caption"],
                "comment" => $validated["comment"] ?? null,
                "date" => now()->getTimestamp(),
            ]
        );

        return new BookmarkResource($bookmark);
    }

    public function destroyBookmark(Bookmark $bookmark)
    {
        abort_unless($bookmark->user_id === Auth::id(), 403);
        $bookmark->delete();

        return response()->json(["message" => "Bookmark removed"]);
    }

    public function storeNote(Request $request)
    {
        $validated = $request->validate([
            "sermonId" => ["required", "integer", "exists:sermons,id"],
            "body" => ["required", "string"],
        ]);

        $note = Note::updateOrCreate(
            [
                "sermon_id" => $validated["sermonId"],
                "user_id" => Auth::id(),
            ],
            [
                "body" => $validated["body"],
                "date" => now()->getTimestamp(),
            ]
        );

        return new NoteResource($note);
    }

    public function destroyNote(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        $note->delete();

        return response()->json(["message" => "Note removed"]);
    }
}
