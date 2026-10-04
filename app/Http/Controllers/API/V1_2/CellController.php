<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Resources\CellResource;
use App\Models\Cell;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CellController extends Controller
{
    public function show(Request $request, $code)
    {
        $cell = Cell::where("code", $code)->first();
        if (is_object($cell)) {
            return response()->json(
                new CellResource($cell)
            );
        } else {
            return response()->json([
                'error' => "Cell not found",
            ], 404);
        }
    }
    public function attachMembers(Request $request, $code)
    {
        $request->validate([
            'members' => 'required'
        ]);


        $cell = Cell::where("code", $code)->first();
        if (is_object($cell)) {

            foreach ($request->members as $member) {
                $member = Member::find($member['id']);
                $member?->update([
                    'cell_id' => $cell?->id,
                ]);
                foreach ($member->users as $user) {
                    $user?->update([
                        'trigger' =>  !boolval($user?->trigger)
                    ]);
                }
            }


            return response()->json(['message' => 'Successfully added members']);
        } else {
            return response()->json([
                'message' => "Cell not found",
            ], 404);
        }
    }

    public function unverified(Request $request)
    {
        $cells = Cell::where("verified", 0)->get();
        return response()->json(CellResource::collection($cells));
    }

    public function update(Request $request, $code)
    {

        Validator::make($request->all(), [
            "name" => "required",
            "zone_id" => "required",
            "balance" => "required",
            "type" => "required",
        ])->validate();

        Cell::where('code', $code)->first()?->update([
            'name' => $request->name,
            'zone_id' => $request->zone_id,
            'balance' => $request->balance,
            'type' => $request->type,
        ]);

        return response()->json([
            "message" => "Cell successfully updated!"
        ]);
    }
}
