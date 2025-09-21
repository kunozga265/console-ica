<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Http\Resources\RegisterResource;
use App\Models\Cell;
use App\Models\Member;
use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class RegisterController extends Controller
{


    public function show(Request $request, $code)
    {
        $register = Register::where("code", $code)->first();

        if (is_object($register)) {
            $members = Member::orderBy("last_name")->get();
            $attendances = $register->members;
            $teen_members = $register->ministry->members;
            $cells = Cell::orderBy("name", "asc")->get();

            return Inertia::render("Registers/Show", [
                "register" => new RegisterResource($register),
                "members" => MemberResource::collection($members),
                "attendances" => MemberResource::collection($attendances),
                "cells" => $cells,

            ]);
        } else {
            return Redirect::back()->with("error", "Register not found!");
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            "ministry_id" => "required",
            "date" => "required",
        ]);

        Register::create([
            "code" => (new AppController())->generateUniqueCode(),
            "name" => $request->name,
            "ministry_id" => $request->ministry_id,
            "date" => (new AppController())->getTimestamp($request->date),
        ]);

        return Redirect::back()->with("success", "New register created!");
    }

    public function recordAttendance(Request $request)
    {
        $request->validate([
            "register_id" => "required",
            "member_id" => "required",
        ]);
        $res = $this->_recordAttendance($request->register_id, $request->member_id);

        if ($res["status"]) {
            return Redirect::back()->with("success", $res["message"]);
        } else {
            return Redirect::back()->with("error", $res["message"]);
        }
    }

    public function _recordAttendance($register_id, $member_id)
    {
        $register = Register::find($register_id);
        $member = Member::find($member_id);

        if ($register->members()->where("member_id", $member->id)->exists()) {
            $register->members()->detach($member);
            return [
                "status" => false,
                "message" => "Unrecorded {$member->fullName()}!"
            ];
        } else {
            $register->members()->attach($member);
             return [
                "status" => true,
                "message" => "Recorded {$member->fullName()}!"
            ];
        }
    }
}
