<?php

namespace App\Http\Controllers\API\V1_2;

use App\Support\AnnotationSync;
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
        $userId = Auth::id();

        foreach ($request->latest ?? [] as $h) {
            AnnotationSync::highlight($userId, $h["sermon_id"], $h["highlight_id"], $h["date"] ?? null);
        }
        foreach ($request->trashed ?? [] as $h) {
            AnnotationSync::unhighlight($userId, $h["sermon_id"], $h["highlight_id"]);
        }

        return response()->json(["message"=>"Successfully synced highlights"]);
    }
}
