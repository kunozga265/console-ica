<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            "sermon_id" => "required",
            "text" => "required",
        ]);

        Bookmark::create([
            "sermon_id" => $request->sermon_id,
            "text" => $request->text,
            "comment" => $request->comment,
            "user_id" => Auth::id(),
        ]);

        return response()->json(["message"=>"Successfully synced bookmarks"]);
    }
}
