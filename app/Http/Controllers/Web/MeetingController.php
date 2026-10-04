<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    public function store(Request $request, $code)
    {
        Validator::make($request->all(), [
            "date" => "required",
            "venue" => "required",
        ])->validate();

        $cell = Cell::where("code", $code)->first();

        $meeting = Meeting::create([
            "code" => (new AppController())->generateUniqueCode(),
            'date' => (new AppController())->getTimestamp($request->date, timeString: $request->time),
            'venue' => $request->venue,
            'cell_id' => $cell->id,
        ]);

        return Redirect::route('cells.show', ['code' => $code])->with('success', 'Meeting created!');
    }

    public function update(Request $request, $code, $meeting_code)
    {
        Validator::make($request->all(), [
            "date" => "required",
            "venue" => "required",
        ])->validate();

        $cell = Cell::where("code", $code)->first();
        $meeting = $cell->meetings()->where("code", $meeting_code)->first();

        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell information not found');
        else if (!is_object($meeting))
            return Redirect::back()->with('error', 'Meeting not found');
        else {
            $meeting->update([
                'date' => (new AppController())->getTimestamp($request->date, timeString: $request->time),
                'venue' => $request->venue,
            ]);

            $transaction = $meeting->transactions()->where("meeting_id", $meeting->id)->first();
            if(is_object($transaction)){
                if ($meeting->cell->transactions()->where("created_at",">",$transaction->created_at)->exists()){
                    return Redirect::back()->with('warning', 'Meeting details updated but not offering: account statement may be violated. Please add offering to next meeting.');
                }else{
                    $amount = $request->offering/2;
                    $old_offering = $transaction->amount;
                    $new_balance = $meeting->cell->balance - $old_offering + $amount;

                    $meeting->update([
                        'offering' => $request->offering,
                    ]);

                    $meeting->cell->update([
                        "balance" => $new_balance
                    ]);

                    $transaction->update([
                        "amount"=> $amount,
                        "description"=> "Cell offering. K" . number_format($amount, 1) . " to be submitted to church.",
                        "balance" => $new_balance
                    ]);
                }

            }else{
                $amount = $request->offering/2;
                $new_balance = $meeting->cell->balance + $amount;

                $meeting->update([
                    'offering' => $request->offering,
                ]);

                $meeting->cell->update([
                    "balance" => $new_balance
                ]);

                Transaction::create([
                    "amount"=> $amount,
                    "type"=> 0,
                    "description"=> "Cell offering. K" . number_format($amount, 1) . " to be submitted to church.",
                    "meeting_id"=> $meeting->id,
                    "cell_id"=> $meeting->cell->id,
                    "balance" => $new_balance
                ]);
            }
        }

        return Redirect::route('cells.show', ['code' => $code])->with('success', 'Meeting updated!');
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
                if(!Attendance::where('member_id', $memberId)->where("meeting_id", $meeting->id)->exists()){
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

    public function trash(Request $request, $code, $meeting_code)
    {
        $cell = Cell::where("code", $code)->first();
        $meeting = $cell->meetings()->where("code", $meeting_code)->first();

        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell information not found');
        else if (!is_object($meeting))
            return Redirect::back()->with('error', 'Meeting not found');
        else {
            //trash attendance
            foreach ($meeting->attendances as $attendance){
                $attendance->delete();
            }
            $meeting->delete();
        }

        return Redirect::route('cells.show', ['code' => $code])->with('success', 'Meeting deleted!');
    }
}
