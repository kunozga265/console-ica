<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\AuthorResource;
use App\Http\Resources\V2\EventResource;
use App\Http\Resources\V2\PrayerResource;
use App\Http\Resources\V2\RegisterResource;
use App\Http\Resources\V2\SeriesResource;
use App\Http\Resources\V2\SermonResource;
use App\Http\Resources\V2\UserResource;
use App\Models\Author;
use App\Models\Event;
use App\Models\Prayer;
use App\Models\Register;
use App\Models\Series;
use App\Models\Sermon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{
    /**
     * Content sync, split per resource type and genuinely paginated —
     * replacing the old /dashboard/{timestamp} endpoint, which capped every
     * type at limit(20) with no way to page further (silently dropping
     * data beyond the first 20 changed records).
     */
    public function sermons(Request $request)
    {
        return SermonResource::collection(
            Sermon::query()
                ->with(["author", "series", "category"])
                ->when($request->query("since"), fn ($q, $since) => $q->where("updated_at", ">", date("Y-m-d H:i:s", $since)))
                ->orderByDesc("published_at")
                ->paginate($request->integer("perPage", 20))
        );
    }

    public function series(Request $request)
    {
        return SeriesResource::collection(
            Series::query()
                ->with("theme")
                ->when($request->query("since"), fn ($q, $since) => $q->where("updated_at", ">", date("Y-m-d H:i:s", $since)))
                ->orderByDesc("first_sermon_date")
                ->paginate($request->integer("perPage", 20))
        );
    }

    public function authors(Request $request)
    {
        return AuthorResource::collection(
            Author::query()
                ->when($request->query("since"), fn ($q, $since) => $q->where("updated_at", ">", date("Y-m-d H:i:s", $since)))
                ->orderBy("name")
                ->paginate($request->integer("perPage", 20))
        );
    }

    public function prayers(Request $request)
    {
        return PrayerResource::collection(
            Prayer::query()
                ->when($request->query("since"), fn ($q, $since) => $q->where("updated_at", ">", date("Y-m-d H:i:s", $since)))
                ->orderByDesc("date")
                ->paginate($request->integer("perPage", 20))
        );
    }

    public function events(Request $request)
    {
        return EventResource::collection(
            Event::query()
                ->when($request->query("since"), fn ($q, $since) => $q->where("updated_at", ">", date("Y-m-d H:i:s", $since)))
                ->orderBy("start_date")
                ->paginate($request->integer("perPage", 20))
        );
    }

    /**
     * The authenticated user's own state: profile, next cell meeting date,
     * and today's/upcoming registers with per-user checked-in status. Kept
     * separate from content sync (which needs no auth) rather than bundled
     * into one kitchen-sink response.
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        $memberCellDate = $user->member?->cell?->nextMeetingDate();
        $leadershipCellDate = $user->member?->leadershipCell?->nextMeetingDate();
        $nextMeetingDate = collect([$memberCellDate, $leadershipCellDate])->filter()->sort()->first();

        $registers = Register::query()
            ->where("date", ">=", now()->startOfDay()->getTimestamp())
            ->orderBy("date")
            ->paginate($request->integer("perPage", 20));

        return response()->json([
            "user" => new UserResource($user),
            "nextMeetingDate" => $nextMeetingDate,
            "registers" => RegisterResource::collection($registers),
        ]);
    }
}
