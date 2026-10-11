<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Web\AppController as WebAppController;
use App\Http\Controllers\Controller;
use App\Support\SermonViews;
use App\Models\Sermon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SermonController extends Controller
{
    public function registerView(Request $request, $slug)
    {
        $sermon = Sermon::where('slug', $slug)->latest('id')->first();
        if (! $sermon) {
            return response()->json(['message' => 'Sermon not found'], 404);
        }

        SermonViews::record($sermon, (new WebAppController())->getAuthUser($request));

        return response()->json();
    }
}
