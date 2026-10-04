<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Http\Resources\HighlightResource;
use App\Models\Highlight;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HighlightController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::id());
        return HighlightResource::collection($user->highlights);
    }

    public function store(Request $request)
    {
        $request->validate([

        ]);

        foreach ($request->latest ?? [] as $highlight){
            if(!Highlight::where("sermon_id",$highlight["sermon_id"])->where("highlight_id", $highlight["highlight_id"])->exists()){
                Highlight::create([
                    "sermon_id" => $highlight["sermon_id"],
                    "highlight_id" => $highlight["highlight_id"],
                    "date" => $highlight["date"],
                    "user_id" => Auth::id(),
                ]);
            }
        }

        foreach ($request->trashed ?? [] as $highlight){
            $highlight = Highlight::where("sermon_id",$highlight["sermon_id"])->where("highlight_id", $highlight["highlight_id"])->first();
            if(is_object($highlight)){
                $highlight->delete();
            }
        }

        return response()->json(["message"=>"Successfully synced highlights"]);
    }
}
