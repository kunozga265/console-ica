<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\Meeting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            "date" => "required",
            "venue" => "required",
            "cell_code" => "required",
        ])->validate();

        $cell = Cell::where("code", $request->cell_code)->first();

        $meeting = Meeting::create([
            "code" => (new AppController())->generateUniqueCode(),
            'date' => $request->date,
            'venue' => $request->venue,
            'cell_id' => $cell->id,
        ]);

        return response()->json([
            "message" => "Meeting created!"
        ]);
    }

    public function update(Request $request, $meeting_code)
    {
        Validator::make($request->all(), [
            "date" => "required",
            "venue" => "required",
            "offering" => "required",
            "cell_code" => "required",
        ])->validate();

        $cell = Cell::where("code", $request->cell_code)->first();
        if(!$cell->verified){
            return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
        }

        $meeting = $cell->meetings()->where("code", $meeting_code)->first();

        if (!is_object($cell))
            return response()->json(['message' => 'Cell not found'], 404);
        else if (!is_object($meeting))
            return response()->json(['message' => 'Meeting not found'], 404);
        else {
            $meeting->update([
                'date' => $request->date,
                'venue' => $request->venue,
            ]);

            if(isset($request->members)){
                foreach ($meeting->attendances as $attendance){
                    $attendance->delete();
                }
                foreach ($request->members as $memberId) {
                    if (!Attendance::where('member_id', $memberId)->where("meeting_id", $meeting->id)->exists()) {
                        Attendance::create([
                            "member_id" => $memberId,
                            "meeting_id" => $meeting->id,
                            "zone_id" => $cell->zone->id,
                        ]);
                    }
                }
            }

            //Update offering
            if ($request->offering != $meeting->offering) {
                $transaction = $meeting->transactions()->where("meeting_id", $meeting->id)->first();
                if (is_object($transaction)) {
                    if ($meeting->cell->transactions()->where("created_at", ">", $transaction->created_at)->exists()) {
                        return response()->json(['message' => 'Offering not updated! Account Statement may be violated. Please add offering to next meeting.'], 400);
                    } else {
                        $amount = $request->offering;
                        $old_offering = $transaction->amount;
                        $new_balance = $meeting->cell->balance - $old_offering + $amount;

                        $meeting->update([
                            'offering' => $request->offering,
                        ]);

                        $meeting->cell->update([
                            "balance" => $new_balance
                        ]);

                        $transaction->update([
                            "amount" => $amount,
                            "description" => "Cell offering",
                            "balance" => $new_balance
                        ]);
                    }

                } else {
                    $amount = $request->offering;
                    $new_balance = $meeting->cell->balance + $amount;

                    $meeting->update([
                        'offering' => $request->offering,
                    ]);

                    $meeting->cell->update([
                        "balance" => $new_balance
                    ]);

                    Transaction::create([
                        "amount" => $amount,
                        "type" => 0,
                        "description" => "Cell offering",
                        "meeting_id" => $meeting->id,
                        "cell_id" => $meeting->cell->id,
                        "balance" => $new_balance
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Meeting updated!'], 200);
    }

    public function recordAttendance(Request $request, $code, $meeting_code)
    {
        Validator::make($request->all(), [
            "members" => "required",
        ])->validate();

        $cell = Cell::where("code", $code)->first();
        $meeting = $cell->meetings()->where("code", $meeting_code)->first();

        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell information not found');
        else if (!is_object($meeting))
            return Redirect::back()->with('error', 'Meeting not found');
        else {
            foreach ($request->members as $memberId) {
                if (!Attendance::where('member_id', $memberId)->where("meeting_id", $meeting->id)->exists()) {
                    Attendance::create([
                        "member_id" => $memberId,
                        "meeting_id" => $meeting->id,
                        "zone_id" => $cell->zone->id,
                    ]);
                }
            }

            return Redirect::route('cells.show', ['code' => $code])->with('success', 'Attendance recorded!');
        }

    }
}
