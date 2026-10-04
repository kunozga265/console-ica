<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\UsageController;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\HighlightResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\V1_3\BookmarkResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\RegisterResource;
use App\Models\Author;
use App\Models\Bookmark;
use App\Models\Cell;
use App\Models\Event;
use App\Models\Highlight;
use App\Models\Note;
use App\Models\Page;
use App\Models\Prayer;
use App\Models\Attendance;
use App\Models\Series;
use App\Models\Register;
use App\Models\Sermon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources;
use App\Http\Resources\RegisterLiteResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppController extends Controller
{

    public $paginate = 20;
    private int $count = 0;


    public function dashboard(Request $request, $timestamp)
    {
        if (!isset($timestamp)) {
            return response()->json([
                "message" => "Timestamp is required."
            ], 400);
        }

        $next_meeting_date = null;
        // if ($request->query("code") !== null) {
        //     $cell = Cell::where("code", $request->query("code"))->first();
        //     if (is_object($cell)) {
        //         $next_meeting_date = $cell->nextMeetingDate();
        //     }
        // }

        //update user
        $user = (new WebAppController())->getAuthUser($request);
        $updatedUser = null;
        if (is_object($user)) {
            $member_cell_date = $user->member?->cell?->nextMeetingDate();
            $leadership_cell_date = $user->member?->leadershipCell?->nextMeetingDate();

            if ($member_cell_date != null && $leadership_cell_date == null) {
                $next_meeting_date = $member_cell_date;
            } else if ($member_cell_date == null && $leadership_cell_date != null) {
                $next_meeting_date = $leadership_cell_date;
            }else{
                if($member_cell_date < $leadership_cell_date){
                    $next_meeting_date = $member_cell_date;
                }else{
                    $next_meeting_date = $leadership_cell_date;
                }
            }

            if ($user->updated_at->getTimestamp() <= $timestamp) {
                $updatedUser = null;
            } else {
                $updatedUser = $user;
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

        if ($request->query("version") !== null) {
            switch ($request->query("version")) {
                case 1:
                case "1":
                    $sermons_collection = Resources\V1_3\SermonResource::collection($sermons);
                    break;
                default:
                    $sermons_collection = Resources\V1_2\SermonResource::collection($sermons);
            }
        } else {
            $sermons_collection = Resources\V1_2\SermonResource::collection($sermons);
        }

        $registers = Register::where('date', '>=', Carbon::today()->getTimestamp())->orderBy('date', 'asc')->paginate((new AppController())->paginate);

        //get new user profile information

        $data = [];
        foreach ($registers as $register) {
            $data[] = [
                "id"                => intval($register->id),
                "code"              => $register->code,
                "name"              => $register->name,
                "ministry"          => $register->ministry,
                "date"              => intval($register->date),
                "active"            => Carbon::createFromTimestamp($register->date)->isToday(),
                "attendees"         => [],
                "checked"           =>  Attendance::where('register_id', $register->id)->where('member_id', $user?->member?->id)->exists()
            ];
        }

        //attach usage record
        (new UsageController())->record($request);


        return response()->json([
            'sermons'   => $sermons_collection,
            'series'    => Resources\SeriesResource::collection($series),
            'authors'   => Resources\AuthorResource::collection($authors),
            'prayer_points'   => Resources\PrayerResource::collection($prayers),
            'events'    => Resources\EventResource::collection($events),
            'announcements'    => new Resources\PageResource($announcements),
            'fundraising'    => new Resources\PageResource($fundraising),
            'next_meeting_date'    => $next_meeting_date,
            'user' => $updatedUser != null ? new UserResource($updatedUser) : null,
            'registers' => $data

        ]);
    }

    public function authData(Request $request)
    {
        $user = User::find(Auth::id());
        return response()->json([
            "highlights" => HighlightResource::collection($user->highlights),
            "bookmarks" => BookmarkResource::collection($user->bookmarks),
            "notes" => NoteResource::collection($user->notes),
        ]);
    }

    public function syncData(Request $request)
    {
        $request->validate([]);


        //highlights
        foreach ($request->input("highlights.latest") ?? [] as $highlight) {
            if (!Highlight::where("sermon_id", $highlight["sermonId"])->where("highlight_id", $highlight["highlightId"])->exists()) {
                Highlight::create([
                    "sermon_id" => $highlight["sermonId"],
                    "highlight_id" => $highlight["highlightId"],
                    "date" => $highlight["date"],
                    "user_id" => Auth::id(),
                ]);
            }
        }

        foreach ($request->input("highlights.trashed") ?? [] as $highlight) {
            $highlight = Highlight::where("sermon_id", $highlight["sermonId"])->where("highlight_id", $highlight["highlightId"])->first();
            if (is_object($highlight)) {
                $highlight->delete();
            }
        }

        //bookmarks
        foreach ($request->bookmarks ?? [] as $bookmark) {

            $existingBookmark = Bookmark::where("sermon_id", $bookmark["sermonId"])
                ->where("user_id", Auth::id())
                ->where("caption_id", $bookmark["captionId"])
                ->first();

            if (is_object($existingBookmark)) {
                $existingBookmark->update([
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

    public function deleteData(Request $request)
    {
        $request->validate([
            "sermon_id" => "required"
        ]);
        $user = User::find(Auth::id());

        if (isset($request->note_id)) {
            $note = $user->notes()->where("sermon_id", $request->sermon_id)->first();

            if (is_object($note)) {
                $note->delete();
                return response()->json(["message" => "Successfully deleted!"], 200);
            } else {
                //if not found
                return response()->json(["message" => "Note not found!"], 404);
            }
        }

        if (isset($request->caption_id)) {
            $bookmark = $user->bookmarks()
                ->where("sermon_id", $request->sermon_id)
                ->where("caption_id", $request->caption_id)
                ->first();

            if (is_object($bookmark)) {
                $bookmark->delete();
                return response()->json(["message" => "Successfully deleted!"], 200);
            } else {
                //if not found
                return response()->json(["message" => "Bookmark not found!"], 404);
            }
        }
    }

    public function generateHighlightLinks($body)
    {
        $body = json_decode(str_replace('<p>', '<p><span>', json_encode($body)));
        $body = json_decode(str_replace('<\/p>', '<\/a><\/span><\/p>', json_encode($body)));
        $body = json_decode(str_replace('<li>', '<li><span>', json_encode($body)));
        $body = json_decode(str_replace('<\/li>', '<\/a><\/span><\/li>', json_encode($body)));
        $body = json_decode(str_replace("<strong> <\/strong>", ' ', json_encode($body)));
        $body = json_decode(str_replace(".<\/strong>", '<\/strong>.', json_encode($body)));
        $body = json_decode(str_replace("&nbsp;", " ", json_encode($body)));

        // correct list spans
        $body = json_decode(str_replace('\r\n\t<ul>', '\r\n\t<\/a><\/span><ul>', json_encode($body)));
        $body = json_decode(str_replace('<\/ul><\/a><\/span><\/li>', '<\/ul><\/li>', json_encode($body)));

        //splits sentences and adds spans
        //those with periods
        $body = json_decode(preg_replace_callback('/ (\w+|\d+|\S+)\. (\w+|\d+|\S+)/', array($this, 'splitSenteces'), json_encode($body)));
        //those with colon
        $body = json_decode(preg_replace_callback('/ (\w+|\d+|\S+)\; (\w+|\d+|\S+)/', array($this, 'splitSentecesWithColon'), json_encode($body)));

        //gives spans ids
        $body = json_decode(preg_replace_callback('/<(span+)(?![^>]*\/>)[^>]*>/', array($this, 'giveSpanIds'), json_encode($body)));

        return $body;
    }

    public function giveSpanIds($matches)
    {
        $this->count++;
        return "<span id='" . $this->count . "' class='data'>";
    }
    public function splitSenteces($matches)
    {
        return " $matches[1]<\/span>. <span>$matches[2]";
    }
    public function splitSentecesWithColon($matches)
    {
        return " $matches[1]<\/span>; <span>$matches[2]";
    }
}
