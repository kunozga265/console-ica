<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\V2\MemberResource;
use App\Http\Resources\V2\MinistryResource;
use App\Http\Resources\V2\RegisterResource;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function index(Request $request)
    {
        $registers = Register::query()
            ->with("ministry")
            ->orderByDesc("date")
            ->paginate($request->integer("perPage", 20));

        return response()->json([
            "registers" => RegisterResource::collection($registers),
            "ministries" => MinistryResource::collection(Ministry::orderBy("name")->get()),
        ]);
    }

    public function attendance($code)
    {
        $register = Register::where("code", $code)->firstOrFail();

        return MemberResource::collection($register->members);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "ministryId" => ["required", "integer", "exists:ministries,id"],
            "date" => ["required", "integer"],
            "name" => ["required", "string"],
        ]);

        $register = Register::create([
            "code" => (new WebAppController())->generateUniqueCode(),
            "name" => $validated["name"],
            "ministry_id" => $validated["ministryId"],
            "date" => $validated["date"],
        ]);

        return new RegisterResource($register);
    }

    /**
     * Bulk toggle-style attendance recording (admin/leader-driven), writing
     * directly to Attendance — same proven approach as the legacy
     * API\V1_3\RegisterController, which itself bypasses the broken
     * Register::members() relation that predated the Phase 2 fix.
     */
    public function recordAttendance(Request $request)
    {
        $validated = $request->validate([
            "attendees" => ["required", "array"],
        ]);

        foreach ($validated["attendees"] as $attendee) {
            $member = Member::find($attendee["memberId"]);
            if (!$member) {
                continue;
            }

            $existing = Attendance::where("member_id", $member->id)
                ->where("register_id", $attendee["registerId"])
                ->first();

            if ($attendee["checked"] ?? false) {
                if (!$existing) {
                    Attendance::create([
                        "member_id" => $member->id,
                        "register_id" => $attendee["registerId"],
                        "zone_id" => $member->cell?->zone_id,
                        "date" => $attendee["date"] ?? now()->getTimestamp(),
                        "meta" => json_encode(["coordinates" => null]),
                    ]);
                }
            } else {
                $existing?->delete();
            }
        }

        return response()->json(["message" => "Attendance recorded"]);
    }

    public function selfRegistration(Request $request, $code)
    {
        $validated = $request->validate([
            "checked" => ["required", "boolean"],
            "latitude" => ["nullable", "numeric"],
            "longitude" => ["nullable", "numeric"],
        ]);

        $register = Register::where("code", $code)->first();
        if (!$register) {
            return response()->json(["message" => "Register not found"], 404);
        }

        $user = Auth::user();
        if (!$user->member) {
            return response()->json(["message" => "Member not found"], 404);
        }

        $existing = Attendance::where("member_id", $user->member->id)
            ->where("register_id", $register->id)
            ->first();

        if ($validated["checked"]) {
            if (!$existing) {
                Attendance::create([
                    "member_id" => $user->member->id,
                    "register_id" => $register->id,
                    "zone_id" => $user->member->cell?->zone_id,
                    "date" => now()->getTimestamp(),
                    "meta" => json_encode([
                        "coordinates" => [
                            "latitude" => $validated["latitude"] ?? null,
                            "longitude" => $validated["longitude"] ?? null,
                        ],
                    ]),
                ]);
            }
        } else {
            $existing?->delete();
        }

        return response()->json(["message" => "Self-registration recorded"]);
    }
}
