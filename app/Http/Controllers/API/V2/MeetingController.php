<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\V2\MeetingResource;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\Transaction;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            "date" => ["required"],
            "venue" => ["required", "string"],
            "cellCode" => ["required", "string", "exists:cells,code"],
        ]);

        $cell = Cell::where("code", $validated["cellCode"])->first();

        $meeting = $cell->meetings()->create([
            "code" => (new WebAppController())->generateUniqueCode(),
            "date" => $validated["date"],
            "venue" => $validated["venue"],
        ]);

        return new MeetingResource($meeting);
    }

    /**
     * Same offering/balance bookkeeping as the legacy V1_2\MeetingController
     * (guards against editing an offering once a later transaction already
     * exists for the cell, so the running balance can't be silently
     * violated), just cleaned up to camelCase.
     */
    public function update(Request $request, $code)
    {
        $validated = $request->validate([
            "date" => ["required"],
            "venue" => ["required", "string"],
            "offering" => ["required", "numeric"],
            "cellCode" => ["required", "string"],
            "members" => ["nullable", "array"],
        ]);

        $cell = Cell::where("code", $validated["cellCode"])->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }
        if (!$cell->verified) {
            return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
        }

        $meeting = $cell->meetings()->where("code", $code)->first();
        if (!$meeting) {
            return response()->json(["message" => "Meeting not found"], 404);
        }

        $meeting->update([
            "date" => $validated["date"],
            "venue" => $validated["venue"],
        ]);

        if (isset($validated["members"])) {
            $meeting->attendances()->delete();
            foreach ($validated["members"] as $memberId) {
                Attendance::create([
                    "member_id" => $memberId,
                    "meeting_id" => $meeting->id,
                    "zone_id" => $cell->zone_id,
                ]);
            }
        }

        if ($validated["offering"] != $meeting->offering) {
            $transaction = $meeting->transactions()->first();

            if ($transaction) {
                if ($cell->transactions()->where("created_at", ">", $transaction->created_at)->exists()) {
                    return response()->json(["message" => "Offering not updated! Account statement may be violated. Please add the offering to the next meeting."], 400);
                }

                $newBalance = $cell->balance - $transaction->amount + $validated["offering"];

                $meeting->update(["offering" => $validated["offering"]]);
                $cell->update(["balance" => $newBalance]);
                $transaction->update([
                    "amount" => $validated["offering"],
                    "description" => "Cell offering",
                    "balance" => $newBalance,
                ]);
            } else {
                $newBalance = $cell->balance + $validated["offering"];

                $meeting->update(["offering" => $validated["offering"]]);
                $cell->update(["balance" => $newBalance]);
                Transaction::create([
                    "amount" => $validated["offering"],
                    "type" => 0,
                    "description" => "Cell offering",
                    "meeting_id" => $meeting->id,
                    "cell_id" => $cell->id,
                    "balance" => $newBalance,
                ]);
            }
        }

        return new MeetingResource($meeting->fresh());
    }
}
