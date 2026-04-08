<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegisterResource;
use App\Http\Resources\SermonResource;
use App\Models\Ministry;
use App\Models\Register;
use App\Models\Sermon;
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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Resources;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{

    public $paginate = 20;

    public function home(Request $request)
    {
        $next_meeting_date = null;
        // if ($request->query("code") !== null) {
        //     $cell = Cell::where("code", $request->query("code"))->first();
        //     if (is_object($cell)) {
        //         $next_meeting_date = $cell->nextMeetingDate();
        //     }
        // }

        //update user
        $user = Auth::user();


        if (is_object($user)) {
            $member_cell_date = $user->member->cell?->nextMeetingDate();
            $leadership_cell_date = $user->member->leadershipCell?->nextMeetingDate();

            if ($member_cell_date != null && $leadership_cell_date == null) {
                $next_meeting_date = $member_cell_date;
            } else if ($member_cell_date == null && $leadership_cell_date != null) {
                $next_meeting_date = $leadership_cell_date;
            } else {
                if ($member_cell_date < $leadership_cell_date) {
                    $next_meeting_date = $member_cell_date;
                } else {
                    $next_meeting_date = $leadership_cell_date;
                }
            }
        }



        //get prayer points
        $now = Carbon::now()->getTimestamp();
        $today = Carbon::today()->getTimestamp();
        $tomorrow = Carbon::tomorrow()->getTimestamp();

        $sermons = Sermon::where('published_at', '<=', $now)->orderByRaw('published_at DESC, created_at DESC')->limit((new AppController())->paginate)->get();
        // orderBy('published_at', 'desc')->orderBy("created_at", "desc")->limit((new AppController())->paginate)->get();
        // $prayers = Prayer::where('date', '<=', $now)->orderByRaw('date DESC, created_at DESC')->orderBy('created_at', 'desc')->limit((new AppController())->paginate)->get();
        $prayer_points = Prayer::where('date', '>=', $today)->where('date', '<', $tomorrow)->first();
        // $series = Series::orderBy("first_sermon_date", "desc")->limit((new AppController())->paginate)->get();
        $events = Event::where('end_date', '>=', $now)->orderBy("start_date", "asc")->limit((new AppController())->paginate)->get();
        // $authors = Author::orderBy('name', 'asc')->get();

        // $announcements = Page::where("name", "announcements")->first();
        // $fundraising = Page::where("name", "fundraising")->first();

        $registers = Register::where('date', '>=', Carbon::today()->getTimestamp())->orderBy('date', 'asc')->paginate((new AppController())->paginate);
        $birthdays = Member::whereRaw(
            'MONTH(FROM_UNIXTIME(date_of_birth)) = ? AND DAY(FROM_UNIXTIME(date_of_birth)) = ?',
            [now()->month, now()->day]
        )->get();

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
        $ministries = Ministry::orderBy("name", "asc")->get();


        return Inertia::render("Home", [
            "sermons" => SermonResource::collection($sermons),
            // 'series'    => Resources\SeriesResource::collection($series),
            // 'authors'   => Resources\AuthorResource::collection($authors),
            'prayerPoints'   => is_object($prayer_points) ? new Resources\PrayerResource($prayer_points) : null,
            'events'    => Resources\EventResource::collection($events),
            'birthdays'    => Resources\MemberResource::collection($birthdays),
            // 'announcements'    => new Resources\PageResource($announcements),
            // 'fundraising'    => new Resources\PageResource($fundraising),
            'next_meeting_date'    => $next_meeting_date,
            'registers' => $data,
            "ministries" => $ministries,
        ]);
    }

    public function dashboard()
    {
        $ministries = Ministry::orderBy("name", "asc")->get();
        $registers = Register::orderBy("date", "desc")->get();

        return Inertia::render("Dashboard", [
            "ministries" => $ministries,
            "registers" => RegisterResource::collection($registers),
        ]);
    }

    public function getTimestamp($dateTimeString, $timeString = null)
    {
        $date = explode('-', $dateTimeString);
        $hour = 0;
        $minutes = 0;

        if ($timeString != null) {
            $time = explode(':', $timeString);
            $hour = $time[0];
            $minutes = $time[1];
        }

        return  Carbon::create($date[0], $date[1], $date[2], $hour, $minutes, 0)->getTimestamp();
    }

    public function generateUniqueCode()
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersNumber = strlen($characters);
        $codeLength = 8;

        do {
            //initialise code to an empty string
            $code = '';

            //generate a code according to the length
            while (strlen($code) < $codeLength) {
                $position = rand(0, $charactersNumber - 1);
                $character = $characters[$position];
                $code .= $character;
            }

            //If the code exists generate another one
        } while (Register::where('code', $code)->exists());

        //return unique code
        return $code;
    }
}
