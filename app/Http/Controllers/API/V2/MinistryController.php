<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\MemberResource;
use App\Http\Resources\V2\MinistryResource;
use App\Models\Ministry;

class MinistryController extends Controller
{
    public function index()
    {
        return MinistryResource::collection(Ministry::orderBy("name")->get());
    }

    public function show($id)
    {
        $ministry = Ministry::find($id);
        if (!$ministry) {
            return response()->json(["message" => "Ministry not found"], 404);
        }

        return response()->json([
            "ministry" => new MinistryResource($ministry),
            "members" => MemberResource::collection($ministry->members),
        ]);
    }
}
