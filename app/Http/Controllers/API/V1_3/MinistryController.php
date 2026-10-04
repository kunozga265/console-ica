<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MinistryResource;
use App\Models\Ministry;
use Illuminate\Http\Request;

class MinistryController extends Controller
{
    public function index()
    {
        $ministries = Ministry::all();
        return response()->json(
            MinistryResource::collection(($ministries))
        );
    }

    public function show(Request $request, $id)
    {
        $ministry = Ministry::find($id);
        return response()->json(
            [
                'ministry' => new MinistryResource($ministry),
                'members' => MemberResource::collection(($ministry->members))
            ]
        );
    }
}
