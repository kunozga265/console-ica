<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Resources\HighlightResource;
use App\Models\Author;
use App\Models\Bookmark;
use App\Models\Cell;
use App\Models\Event;
use App\Models\Highlight;
use App\Models\Note;
use App\Models\Page;
use App\Models\Prayer;
use App\Models\Series;
use App\Models\Sermon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{

    public $paginate = 20;


    public function dashboard(Request $request, $timestamp)
    {
        if (!isset($timestamp)) {
            return response()->json([
                "message" => "Timestamp is required."
            ], 400);
        }

        $next_meeting_date = null;
        if ($request->query("code") !== null) {
            $cell = Cell::where("code", $request->query("code"))->first();
            if (is_object($cell)) {
                $next_meeting_date = $cell->nextMeetingDate();
            }
        }

        //get prayer points
        //        $now=Carbon::now()->getTimestamp(collection);
        if ($timestamp == 0) {
            $now = Carbon::now()->getTimestamp();
            $prayers = Prayer::where('date', '<=', $now)->orderBy('date', 'desc')->limit((new \App\Http\Controllers\API\V1_1\AppController())->paginate)->get();
            $sermons = Sermon::orderBy("published_at", "desc")->limit((new AppController())->paginate)->get();
            $series = Series::where("first_sermon_date", "!=", null)->orderBy("first_sermon_date", "desc")->limit((new AppController())->paginate)->get();
            $events = Event::orderBy("start_date", "desc")->limit((new AppController())->paginate)->get();
            $authors = Author::all();
        } else {
            $prayers = Prayer::where('date', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy('date', 'desc')->limit((new AppController())->paginate)->get();
            $sermons = Sermon::where('published_at', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy("published_at", "desc")->limit((new AppController())->paginate)->get();
            $series = Series::where('updated_at', '>', Carbon::createFromTimestamp($timestamp))->where("first_sermon_date", "!=", null)->orderBy("first_sermon_date", "desc")->limit((new AppController())->paginate)->get();
            $events = Event::where('start_date', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy("start_date", "desc")->limit((new AppController())->paginate)->get();
            $authors = Author::where('updated_at', '>', Carbon::createFromTimestamp($timestamp))->get();
        }

        $announcements = Page::where("name", "announcements")->first();
        $fundraising = Page::where("name", "fundraising")->first();

        return response()->json([
            'sermons'   => Resources\V1_2\SermonResource::collection($sermons),
            'series'    => Resources\SeriesResource::collection($series),
            'authors'   => Resources\AuthorResource::collection($authors),
            'prayer_points'   => Resources\PrayerResource::collection($prayers),
            'events'    => Resources\EventResource::collection($events),
            'announcements'    => new Resources\PageResource($announcements),
            'fundraising'    => new Resources\PageResource($fundraising),
            'next_meeting_date'    => $next_meeting_date,

        ]);
    }

    public function authData(Request $request)
    {
        $user = User::find(Auth::id());
        return response()->json([
            "highlights" => $user->highlights,
            "bookmarks" => $user->bookmarks,
            "notes" => $user->notes,
        ]);
    }

    public function syncData(Request $request)
    {
        $request->validate([]);


        //highlights
        foreach ($request->input("highlights.latest") ?? [] as $highlight) {
            if (!Highlight::where("sermon_id", $highlight["sermon_id"])->where("highlight_id", $highlight["highlight_id"])->exists()) {
                Highlight::create([
                    "sermon_id" => $highlight["sermon_id"],
                    "highlight_id" => $highlight["highlight_id"],
                    "date" => $highlight["date"],
                    "user_id" => Auth::id(),
                ]);
            }
        }

        foreach ($request->input("highlights.trashed") ?? [] as $highlight) {
            $highlight = Highlight::where("sermon_id", $highlight["sermon_id"])->where("highlight_id", $highlight["highlight_id"])->first();
            if (is_object($highlight)) {
                $highlight->delete();
            }
        }

        //bookmarks
        foreach ($request->bookmarks ?? [] as $bookmark) {

            $bookmark = Bookmark::where("sermon_id", $bookmark["sermonId"])
                ->where("user_id", Auth::id())
                ->where("caption_id", $bookmark["captionId"])
                ->first();

            if (is_object($bookmark)) {
                $bookmark->update([
                    "sermon_id" => $bookmark["sermonId"],
                    "caption" => $bookmark["caption"],
                    "caption_id" => $bookmark["captionId"],
                    "date" => $bookmark["date"],
                    "comment" => $bookmark["comment"],
                    "user_id" => Auth::id(),
                ]);
            } else {
                Bookmark::create([
                    "sermon_id" => $bookmark["sermonId"],
                    "caption" => $bookmark["caption"],
                    "caption_id" => $bookmark["captionId"],
                    "date" => $bookmark["date"],
                    "comment" => $bookmark["comment"],
                    "user_id" => Auth::id(),
                ]);
            }
        }

        //notes
        foreach ($request->notes ?? [] as $note) {
            $_note = Note::where("sermon_id", $note["sermonId"])->where("user_id", Auth::id())->first();
            if (is_object($_note)) {
                $_note->update([
                    "sermon_id" => $note["sermonId"],
                    "body" => $note["body"],
                    "date" => $note["date"],
                    "user_id" => Auth::id(),
                ]);
            } else {
                Note::create([
                    "sermon_id" => $note["sermonId"],
                    "body" => $note["body"],
                    "date" => $note["date"],
                    "user_id" => Auth::id(),
                ]);
            }
        }

        return response()->json(["message" => "Successfully synced data"]);
    }
}
