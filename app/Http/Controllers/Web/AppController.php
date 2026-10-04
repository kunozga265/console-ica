<?php

namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;

use App\Http\Resources\RegisterResource;
use App\Http\Resources\SermonResource;
use App\Models\Ministry;
use App\Models\Register;
use App\Models\Sermon;
use App\Models\Author;
use App\Models\Bookmark;
use App\Models\Cell;
use App\Models\Event;
use App\Models\Highlight;
use App\Models\Note;
use App\Models\Page;
use App\Models\Prayer;
use App\Models\Attendance;
use App\Models\Series;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Resources;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AppController extends Controller
{

    public $paginate = 20;

    public function getTimestamp($dateTimeString, $timeString = null)
    {
        $date = explode('-', $dateTimeString);
        $hour = 0;
        $minutes = 0;

        if ($timeString != null) {
            $time = explode(':', $timeString);
            $hour = $time[0];
            $minutes = $time[1];
        }

        return  Carbon::create($date[0], $date[1], $date[2], $hour, $minutes, 0)->getTimestamp();
    }

    public function generateUniqueCode()
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersNumber = strlen($characters);
        $codeLength = 8;

        do {
            //initialise code to an empty string
            $code = '';

            //generate a code according to the length
            while (strlen($code) < $codeLength) {
                $position = rand(0, $charactersNumber - 1);
                $character = $characters[$position];
                $code .= $character;
            }

            //If the code exists generate another one
        } while (Register::where('code', $code)->exists());

        //return unique code
        return $code;
    }

    public function isApi(Request $request)
    {
        //get cookie object
        $CSRF_TOKEN = $request->cookie();
        return count($CSRF_TOKEN) == 0;
    }

    public function getAuthUser(Request $request)
    {
        if ($this->isApi($request)) {
            //API User
            $requestToken = substr($request->server('HTTP_AUTHORIZATION'), 7);

            if ($requestToken) {
                $token = PersonalAccessToken::findToken($requestToken);
                return $token?->tokenable;
            } else
                return null;
        } else {
            return User::find(Auth::id());
        }
    }
}
