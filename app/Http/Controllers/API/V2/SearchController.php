<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\AuthorResource;
use App\Http\Resources\V2\SeriesResource;
use App\Http\Resources\V2\SermonResource;
use App\Models\Author;
use App\Models\Series;
use App\Models\Sermon;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(["query" => ["required", "string"]]);
        $query = $request->query("query");

        return response()->json([
            "sermons" => SermonResource::collection(
                Sermon::where("title", "like", "%{$query}%")->limit(20)->get()
            ),
            "series" => SeriesResource::collection(
                Series::where("title", "like", "%{$query}%")->limit(20)->get()
            ),
            "authors" => AuthorResource::collection(
                Author::where("name", "like", "%{$query}%")->limit(20)->get()
            ),
        ]);
    }
}
