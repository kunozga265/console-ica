<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\API\V1_1\AppController;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Series;
use App\Models\Sermon;
use Illuminate\Http\Request;
use App\Http\Resources;

class SermonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $sermons= Sermon::orderBy("published_at","desc")->paginate((new AppController())->paginate);
        $series= Series::where("first_sermon_date","!=",null)->orderBy("first_sermon_date","desc")->paginate((new AppController())->paginate);
        $authors= Author::all();

        return response()->json([
            'sermons'   => new Resources\V1_2\SermonCollection($sermons),
            'series'    => new Resources\SeriesCollection($series),
            'authors'   => Resources\AuthorResource::collection($authors)
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bySeries($slug)
    {
        $series = Series::where('slug','=',$slug)->first();

        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            $sermons=$series->sermons()->orderBy("published_at","desc")->get();

            return response()->json(Resources\V1_2\SermonResource::collection($sermons), 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSermonsByAuthor($slug)
    {
        $author = Author::where('slug','=',$slug)->first();
        if (!is_object($author))
            return response()->json(["response"=>false],204);
        else {
//            $sermons= Author::where('slug',$slug)->first()->sermons()->orderBy("published_at","desc")->paginate((new AppController())->paginate);
//            return response()->json(new Resources\V1_1\SermonCollection($sermons), 200);
            $sermons=$author->sermons()->orderBy("published_at","desc")->get();
            return response()->json(Resources\V1_2\SermonResource::collection($sermons), 200);
        }
    }

    public function getSermons($timestamp)
    {
        $sermons = Sermon::where("published_at","<",$timestamp)->orderby("published_at","desc")->get();
        return response()->json(Resources\V1_2\SermonResource::collection($sermons),200);
    }

     /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug)
    {
        $sermon = Sermon::where('slug','=',$slug)->first();
        if (!is_object($sermon))
            return response()->json(["response"=>false],404);
        else {
            /* We will use this elsewhere
            $view=View::where("sermon_id",$sermon->id)->first();
            $view->update([
                "count"=>($view->count)+1
            ]);*/

//            if ($sermon->series_id!=null)
//                $sermonSeries=Sermon::where("series_id","=",$sermon->series_id)->where("id","!=",$sermon->id)->orderBy("published_at","desc")->get();
//            else
//                $sermonSeries=[];

            return response()->json(new Resources\SermonResource($sermon), 200);
        }
    }
}
