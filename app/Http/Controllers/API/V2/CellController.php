<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\V2\CellResource;
use App\Models\Cell;
use App\Models\Member;
use Illuminate\Http\Request;

class CellController extends Controller
{
    public function show($code)
    {
        $cell = Cell::where("code", $code)->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }

        return new CellResource($cell);
    }

    public function unverified()
    {
        return CellResource::collection(Cell::where("verified", false)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => ["required", "string"],
            "zoneId" => ["required", "integer", "exists:zones,id"],
            "balance" => ["required", "numeric"],
            "type" => ["required", "integer"],
            "userId" => ["required", "integer", "exists:users,id"],
        ]);

        $cell = Cell::create([
            "code" => (new WebAppController())->generateUniqueCode(),
            "name" => $validated["name"],
            "details" => $request->details,
            "location" => $request->location,
            "zone_id" => $validated["zoneId"],
            "balance" => $validated["balance"],
            "type" => $validated["type"],
            "user_id" => $validated["userId"],
            "verified" => false,
        ]);

        return new CellResource($cell);
    }

    public function update(Request $request, $code)
    {
        $validated = $request->validate([
            "name" => ["required", "string"],
            "zoneId" => ["required", "integer", "exists:zones,id"],
            "balance" => ["required", "numeric"],
            "type" => ["required", "integer"],
        ]);

        $cell = Cell::where("code", $code)->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }

        $cell->update([
            "name" => $validated["name"],
            "zone_id" => $validated["zoneId"],
            "balance" => $validated["balance"],
            "type" => $validated["type"],
        ]);

        return new CellResource($cell);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate(["code" => ["required", "string"]]);

        $cell = Cell::where("code", $validated["code"])->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }

        $cell->update(["verified" => true]);

        return response()->json(["message" => "Successfully verified!"]);
    }

    public function attachMembers(Request $request, $code)
    {
        $validated = $request->validate(["members" => ["required", "array"]]);

        $cell = Cell::where("code", $code)->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }

        foreach ($validated["members"] as $memberInput) {
            $member = Member::find($memberInput["id"]);
            $member?->update(["cell_id" => $cell->id]);
        }

        return response()->json(["message" => "Successfully added members"]);
    }
}
