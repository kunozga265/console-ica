<?php

namespace App\Http\Controllers;


use App\Models\Usage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsageController extends Controller
{

    public function record(Request $request)
    {
        $usage = $this->getLatest();
        $usage->update([
            'count' => $usage->count + 1
        ]);
        $user = Auth::user();

        if (!$usage->users()->where("user_id", $user?->id)->exists()) {
            $usage->users()->attach($user);
        }
    }

    public function getLatest()
    {
        $last = Usage::orderBy("date", "desc")->first();
        $today = Carbon::today()->getTimestamp();

        if (is_object($last)) {
            //check if last summary is less than today's date
            if ($last->date < $today) {
                //close summary
                return Usage::create([
                    "date" => $today,
                ]);
            } else {
                return $last;
            }
        } else {
            //create new summary for today
            return Usage::create([
                "date" => $today,
            ]);
        }
    }
}
