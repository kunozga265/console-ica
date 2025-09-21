<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class MemberController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            "firstName" => "required",
            "lastName" => "required",
            "gender" => "required",
        ]);

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
            "code" => (new AppController())->generateUniqueCode(),
            "avatar"        =>  $avatar,
            'first_name' => $request->firstName,
            'middle_name' => $request->middleName,
            'other_name' => $request->otherName,
            'last_name' => $request->lastName,
            'gender' => $request->gender,
            'cell_id' => $request->cellId,
            'phone_number_airtel' => str_replace(" ", "", $request->phoneNumberAirtel),
            'phone_number_tnm' => str_replace(" ", "", $request->phoneNumberTnm),
            'phone_number_international' => str_replace(" ", "", $request->phoneNumberInternational),
            'email' => $request->email,
            "date_of_birth"  =>  $request->dateOfBirth

        ]);

        if ($request->checkAttendance && isset($request->registerId)) {
            (new RegisterController())->_recordAttendance($request->registerId, $member->id);
        }

        return Redirect::back()->with('success', 'Member created!');
    }
}
