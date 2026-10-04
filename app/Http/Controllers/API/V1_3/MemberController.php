<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\MemberResource;
use App\Models\Cell;
use App\Models\Member;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class MemberController extends Controller
{

    public function index()
    {
        $members = Member::orderBy('first_name', 'asc')->get();
        return response()->json(MemberResource::collection($members),);
    }

    public function store(Request $request)
    {

        $request->validate([
            "first_name" => "required",
            "last_name" => "required",
            "gender" => "required",
            // "type" => "required",
            // "cell_code" => "required",
        ]);

        $cell = Cell::where("code", $request->cell_code)->first();

        if ($request->type == "CELL") {

            $request->validate([
                "cell_code" => "required",
            ]);

            if (!$cell->verified) {
                return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
            }
        }

        if (!isset($request->phone_number_airtel) && !isset($request->phone_number_tnm) && !isset($request->phone_number_international)) {
            return response()->json(["message" => "Please enter at least one phone number"], 400);
        } else if (isset($request->phone_number_airtel) && Member::where("phone_number_airtel", $request->phone_number_airtel)->exists()) {

            $member = Member::where("phone_number_airtel", $request->phone_number_airtel)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this airtel number already exists"
            ], 406);
        } else if (isset($request->phone_number_tnm) && Member::where("phone_number_tnm", $request->phone_number_tnm)->exists()) {

            $member = Member::where("phone_number_tnm", $request->phone_number_tnm)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this tnm number already exists"
            ], 406);
        } else if (isset($request->phone_number_international) && Member::where("phone_number_international", $request->phone_number_international)->exists()) {

            $member = Member::where("phone_number_international", $request->phone_number_international)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this international number already exists"
            ], 406);
        }

        $slug = Str::slug($request->first_name . "-" . $request->last_name) . date("-Y-m-d");
        $avatar = "images/avatar.png";

        if (isset($request->avatar)) {
            $filename = $slug . uniqid() . "." . $request->avatar->extension();
            try {
                $request->avatar->move(public_path('images/members'), $filename);
                $avatar = "images/members/$filename";
            } catch (FileException $exception) {
                //catch file exception
            }
        }

        $member = Member::create([
            "code" => (new \App\Http\Controllers\Web\AppController())->generateUniqueCode(),
            "avatar" => $avatar,
            'first_name' => ucwords($request->first_name),
            'middle_name' => ucwords($request->middle_name),
            'other_name' => ucwords($request->other_name),
            'last_name' => ucwords($request->last_name),
            'gender' => $request->gender,
            'cell_id' => $cell?->id,
            'phone_number_airtel' => $request->phone_number_airtel,
            'phone_number_tnm' => $request->phone_number_tnm,
            'phone_number_international' => $request->phone_number_international,
            'email' => $request->email,
            "date_of_birth" => $request->date_of_birth

        ]);

        return response()->json(["message" => "Member added!"]);
    }

    public function batchAdd(Request $request)
    {

        $request->validate([
            "members" => "required",
        ]);


        $avatar = "images/avatar.png";

        foreach ($request->members as $member) {


            $member = Member::updateOrCreate([
                'first_name' => $member["first_name"],
                'last_name' => $member["last_name"],
                "date_of_birth" => $member["date_of_birth"]
            ], [
                "code" => (new \App\Http\Controllers\Web\AppController())->generateUniqueCode(),
                "avatar" => $avatar,
                'email' => $member["email"],
                'middle_name' => $member["middle_name"],
                'other_name' => $member["other_name"],

                'gender' => $member["gender"],
                'cell_id' => $member["cell_id"],
                'phone_number_airtel' => $member["phone_number_airtel"],
                'phone_number_tnm' => $member["phone_number_tnm"],
                'phone_number_international' => $member["phone_number_international"],

            ]);
        }


        return response()->json(["message" => "Members added!"]);
    }

    public function update(Request $request, $code)
    {
        $member = Member::where("code", $code)->first();
        if (!is_object($member))
            return response()->json(["message" => "Member not found"], 400);
        else {
            //update

            Validator::make($request->all(), [
                "first_name" => "required",
                "last_name" => "required",
                "gender" => "required",
            ])->validate();

            $member->update([
                'first_name' => ucwords($request->first_name),
                'middle_name' => ucwords($request->middle_name),
                'last_name' => ucwords($request->last_name),
                'gender' => $request->gender,
                'phone_number_airtel' => $request->phone_number_airtel,
                'phone_number_tnm' => $request->phone_number_tnm,
                'phone_number_international' => $request->phone_number_international,
                'email' => $request->email,
                "date_of_birth" => $request->date_of_birth
            ]);

            return response()->json(["message" => "Members updated!"]);
        }
    }
}
