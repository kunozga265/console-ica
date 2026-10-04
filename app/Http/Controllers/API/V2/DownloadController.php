<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\DownloadResource;
use App\Models\Download;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        return DownloadResource::collection(
            Download::orderByDesc("date")->paginate($request->integer("perPage", 20))
        );
    }
}
