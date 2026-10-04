<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebController;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MinistryResource;
use App\Http\Resources\RegisterLiteResource;
use App\Http\Resources\RegisterResource;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\Register;
use App\Models\Attendance;
use App\Models\User;
use Google\Service\AlertCenter\RequestInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RegisterController extends Controller
{
    // $paginate = 100
    public function index()
    {
        $registers = Register::orderBy('date', 'desc')->paginate((new AppController())->paginate);

        $members = Member::orderBy('first_name', 'asc')->get();
        $ministries = Ministry::orderBy('name', 'asc')->get();

        return response()->json([
            'registers' => RegisterResource::collection($registers),
            'members' => MemberResource::collection($members),
            'ministries' => MinistryResource::collection($ministries),
        ]);
    }

    public function attendance(Request $request, $code)
    {
        $register = Register::where('code', $code)->first();
        return response()->json(MemberResource::collection($register->members));
    }

    public function store(Request $request)
    {
        $request->validate([
            "ministry_id" => "required",
            "date" => "required",
            "name" => "required",
        ]);

        Register::create([
            "code" => (new WebController())->generateUniqueCode(),
            "name" => $request->name,
            "ministry_id" => $request->ministry_id,
            "date" => $request->date,
        ]);

        return response()->json(["message" => "Successfully create service"]);
    }

    public function recordAttendance(Request $request)
    {

        $request->validate([
            "attendees" => "required",
        ]);

        foreach ($request->attendees as $attendee) {

            $register = Register::where('id', $attendee['register_id'])->first();
            $member = Member::where('id', $attendee['member_id'])->first();


            if ($attendee["checked"] || $attendee["checked"] == '1' || $attendee["checked"] == 1) {
                // if (!$register?->members()->where('member_id', $member->id)->exists()) {
                //     $register?->members()->attach($member);
                // }

                if (!Attendance::where('member_id', $member->id)->where("register_id", $register->id)->exists()) {
                    Attendance::create([
                        "member_id" => $member->id,
                        "register_id" => $register->id,
                        "zone_id" => $member->cell?->zone?->id,
                        "date" => $attendee['date'],
                        "meta" => json_encode([
                            "coordinates" => null
                        ])
                    ]);
                }
            } else {
                // $register->members()->detach($member);
                if (Attendance::where('member_id', $member->id)->where("register_id", $register->id)->exists()) {
                    Attendance::where('member_id', $member->id)->where("register_id", $register->id)->delete();
                }
            }
        }

        return response()->json();
    }
    public function selfRegistration(Request $request, $code)
    {

        $request->validate([
            "checked" => "required",
        ]);

        $user = User::find(Auth::id());

        $register = Register::where('code', $code)->first();

        if (!is_object($register)) {
            return response()->json([
                "message" => "Register not found"
            ], 400);
        }

        if ($user->member == null) {
            return response()->json([
                "message" => "Member not found"
            ], 404);
        }

        if ($request->checked) {
            // if (!$register?->members()->where('member_id', $user->member->id)->exists()) {
            //     $register?->members()->attach($user->member);
            // }

            if (!Attendance::where('member_id', $user->member->id)->where("register_id", $register->id)->exists()) {
                Attendance::create([
                    "member_id" => $user->member->id,
                    "register_id" => $register->id,
                    "zone_id" => $user->member?->cell?->zone?->id,
                    "date" => Carbon::now()->getTimestamp(),
                    "meta" => json_encode([
                        "coordinates" => [
                            "latitude" => $request->latitude,
                            "longitude" => $request->longitude,
                        ]
                    ])
                ]);
            }
        } else {
            // $register->members()->detach($user->member);

            if (Attendance::where('member_id', $user->member->id)->where("register_id", $register->id)->exists()) {
                Attendance::where('member_id', $user->member->id)->where("register_id", $register->id)->delete();
            }
        }

        return response()->json();
    }
}
