<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Cell;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class MemberController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            "first_name" => "required",
            "last_name" => "required",
            "gender" => "required",
            "cell_code" => "required",
        ]);

        $cell = Cell::where("code", $request->cell_code)->first();
        if(!$cell->verified){
            return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
        }

        if (!isset($request->phone_number_airtel) && !isset($request->phone_number_tnm) && !isset($request->phone_number_international)) {
            return response()->json(["message" => "Please enter at least one phone number"], 400);
        } else if (isset($request->phone_number_airtel) && Member::where("phone_number_airtel", $request->phone_number_airtel)->exists()) {

            $member = Member::where("phone_number_airtel", $request->phone_number_airtel)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this airtel number already exists"], 406);

        } else if (isset($request->phone_number_tnm) && Member::where("phone_number_tnm", $request->phone_number_tnm)->exists()) {

            $member = Member::where("phone_number_tnm", $request->phone_number_tnm)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this tnm number already exists"], 406);

        } else if (isset($request->phone_number_international) && Member::where("phone_number_international", $request->phone_number_international)->exists()) {

            $member = Member::where("phone_number_international", $request->phone_number_international)->first();
            return response()->json([
                "member" => new MemberResource($member),
                "message" => "Member with this international number already exists"], 406);
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
            'cell_id' => $cell->id,
            'phone_number_airtel' => $request->phone_number_airtel,
            'phone_number_tnm' => $request->phone_number_tnm,
            'phone_number_international' => $request->phone_number_international,
            'email' => $request->email,
            "date_of_birth" => $request->date_of_birth

        ]);

        return response()->json(["message" => "Member added!"]);
    }
}
