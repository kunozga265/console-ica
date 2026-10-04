<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Resources\V2\MemberResource;
use App\Models\Cell;
use App\Models\Member;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        return MemberResource::collection(
            Member::query()
                ->with("cell")
                ->orderBy("first_name")
                ->paginate($request->integer("perPage", 20))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "firstName" => ["required", "string"],
            "lastName" => ["required", "string"],
            "gender" => ["required", "string"],
            "cellCode" => ["nullable", "string"],
            "phoneNumberAirtel" => ["nullable", "string"],
            "phoneNumberTnm" => ["nullable", "string"],
            "phoneNumberInternational" => ["nullable", "string"],
            "email" => ["nullable", "email"],
            "dateOfBirth" => ["nullable", "integer"],
        ]);

        if (
            !$request->filled("phoneNumberAirtel")
            && !$request->filled("phoneNumberTnm")
            && !$request->filled("phoneNumberInternational")
        ) {
            return response()->json(["message" => "Please enter at least one phone number"], 400);
        }

        foreach (["phoneNumberAirtel" => "phone_number_airtel", "phoneNumberTnm" => "phone_number_tnm", "phoneNumberInternational" => "phone_number_international"] as $field => $column) {
            if ($request->filled($field)) {
                $existing = Member::where($column, $validated[$field])->first();
                if ($existing) {
                    return response()->json([
                        "member" => new MemberResource($existing),
                        "message" => "A member with this number already exists",
                    ], 406);
                }
            }
        }

        $cell = $request->filled("cellCode") ? Cell::where("code", $request->cellCode)->first() : null;

        $member = Member::create([
            "code" => (new WebAppController())->generateUniqueCode(),
            "avatar" => "images/avatar.png",
            "first_name" => ucwords($validated["firstName"]),
            "last_name" => ucwords($validated["lastName"]),
            "gender" => $validated["gender"],
            "cell_id" => $cell?->id,
            "phone_number_airtel" => $validated["phoneNumberAirtel"] ?? null,
            "phone_number_tnm" => $validated["phoneNumberTnm"] ?? null,
            "phone_number_international" => $validated["phoneNumberInternational"] ?? null,
            "email" => $validated["email"] ?? null,
            "date_of_birth" => $validated["dateOfBirth"] ?? null,
        ]);

        return new MemberResource($member);
    }

    public function update(Request $request, $code)
    {
        $member = Member::where("code", $code)->first();
        if (!$member) {
            return response()->json(["message" => "Member not found"], 404);
        }

        $validated = $request->validate([
            "firstName" => ["required", "string"],
            "lastName" => ["required", "string"],
            "gender" => ["required", "string"],
            "phoneNumberAirtel" => ["nullable", "string"],
            "phoneNumberTnm" => ["nullable", "string"],
            "phoneNumberInternational" => ["nullable", "string"],
            "email" => ["nullable", "email"],
            "dateOfBirth" => ["nullable", "integer"],
        ]);

        $member->update([
            "first_name" => ucwords($validated["firstName"]),
            "last_name" => ucwords($validated["lastName"]),
            "gender" => $validated["gender"],
            "phone_number_airtel" => $validated["phoneNumberAirtel"] ?? null,
            "phone_number_tnm" => $validated["phoneNumberTnm"] ?? null,
            "phone_number_international" => $validated["phoneNumberInternational"] ?? null,
            "email" => $validated["email"] ?? null,
            "date_of_birth" => $validated["dateOfBirth"] ?? null,
        ]);

        return new MemberResource($member);
    }

    public function batchAdd(Request $request)
    {
        $validated = $request->validate([
            "members" => ["required", "array"],
        ]);

        $created = collect($validated["members"])->map(function ($input) {
            return Member::updateOrCreate(
                [
                    "first_name" => $input["firstName"],
                    "last_name" => $input["lastName"],
                    "date_of_birth" => $input["dateOfBirth"] ?? null,
                ],
                [
                    "code" => (new WebAppController())->generateUniqueCode(),
                    "avatar" => "images/avatar.png",
                    "email" => $input["email"] ?? null,
                    "gender" => $input["gender"],
                    "cell_id" => $input["cellId"] ?? null,
                    "phone_number_airtel" => $input["phoneNumberAirtel"] ?? null,
                    "phone_number_tnm" => $input["phoneNumberTnm"] ?? null,
                    "phone_number_international" => $input["phoneNumberInternational"] ?? null,
                ]
            );
        });

        return MemberResource::collection($created);
    }
}
