<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegisterResource;
use App\Models\Ministry;
use App\Models\Register;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppController extends Controller
{
    public function dashboard()
    {
        $ministries = Ministry::orderBy("name","asc")->get();
        $registers = Register::orderBy("date", "desc")->get();

        return Inertia::render("Dashboard",[
            "ministries" => $ministries,
            "registers" => RegisterResource::collection($registers),
        ]);

    }

    public function getTimestamp($dateTimeString, $timeString = null)
    {
        $date=explode('-',$dateTimeString);
        $hour = 0;
        $minutes = 0;

        if($timeString != null){
            $time=explode(':',$timeString);
            $hour = $time[0];
            $minutes = $time[1];
        }

        return  Carbon::create($date[0],$date[1],$date[2],$hour,$minutes,0)->getTimestamp();
    }

    public function generateUniqueCode()
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersNumber = strlen($characters);
        $codeLength = 8;

        do{
            //initialise code to an empty string
            $code='';

            //generate a code according to the length
            while (strlen($code) < $codeLength) {
                $position = rand(0, $charactersNumber - 1);
                $character = $characters[$position];
                $code .= $character;
            }

            //If the code exists generate another one
        }while(Register::where('code',$code)->exists());

        //return unique code
        return $code;
    }
}
