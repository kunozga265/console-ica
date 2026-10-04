<?php

namespace App\Http\Controllers\API\V1_0_0;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Validator;

class SeriesController extends Controller
{
    public function search($query){
        $series=Series::where('title', 'like', '%' .$query. '%')->orderBy('title','asc')->paginate((new AppController())->paginate);
        return response()->json(new Resources\SeriesCollection($series),200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $series= Series::where("first_sermon_date","!=",null)->orderBy("first_sermon_date","desc")->paginate((new AppController())->paginate);
        return response()->json(new Resources\SeriesCollection($series),200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function options()
    {
        $series= Series::orderBy("title","asc")->get();
        return response()->json(Resources\SeriesOptionsResource::collection($series),200);
    }

    /**
     * Display a listing of the resource.
     *
     * @param string $filter
     * @param string $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiltered($filter,$query)
    {
        switch ($filter){
            case "Published":
                $series= Series::where("first_sermon_date","!=",null)->orderBy("first_sermon_date","desc")->paginate((new AppController())->paginate);
                break;
            case "Unpublished":
                $series= Series::where("first_sermon_date",null)->orderBy("title","asc")->paginate((new AppController())->paginate);
                break;
            case "Trashed":
                $series= Series::onlyTrashed()->orderBy("title","asc")->paginate((new AppController())->paginate);
                break;
            case "Search":
                $series=Series::search($query)->withTrashed()->paginate((new AppController())->paginate);
                break;
            default:
                return response()->json([],204);
        }
        return response()->json(new Resources\SeriesCollection($series),200);
    }

    /**
     * Display a listing of the resource.
     * @param $sort
     * @param $fromDate
     * @param $endDate
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSeries($sort, $fromDate, $endDate)
    {
        $pagination_items=(new AppController())->paginate;
        switch ($sort){
            case "TITLE_ASC":
                $series= Series::where("first_sermon_date","<=",$fromDate)->where("first_sermon_date",">=",$endDate)->orderBy("title","asc")->paginate($pagination_items);
                break;
            case "TITLE_DESC":
                $series= Series::where("first_sermon_date","<=",$fromDate)->where("first_sermon_date",">=",$endDate)->orderBy("title","desc")->paginate($pagination_items);
                break;
            case "DATE_ASC":
                $series= Series::where("first_sermon_date","<=",$fromDate)->where("first_sermon_date",">=",$endDate)->orderBy("first_sermon_date","asc")->paginate($pagination_items);
                break;
            case "DATE_DESC":
                $series= Series::where("first_sermon_date","<=",$fromDate)->where("first_sermon_date",">=",$endDate)->orderBy("first_sermon_date","desc")->paginate($pagination_items);
                break;
            default:
                $series=[];
        }

        return response()->json(new Resources\SeriesCollection($series),200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator=Validator::make($request->all(),[
            "title"     =>  "required",
        ]);

        if ($validator->fails()){
            return response()->json(["message"=>"Title attribute required"],400);
        }

        $series=new Series([
            "title"             =>  $request->title,
            "slug"              =>  Str::slug($request->title).date("-Y-m-d"),
//            "description"       =>  Purifier::clean($request->description),
            "description"       =>  $request->description,
            "theme_id"          =>  $request->theme_id,
        ]);

        $series->save();

        return response()->json(["series"=>new Resources\SeriesResource($series)],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $slug
     * @param  string $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($slug,$filter)
    {
        $series = Series::where('slug','=',$slug)->first();

        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            if ($filter=="Filter")
                $sermons=[];
            else
                $sermons=$series->sermons()->orderBy("published_at","desc")->get();


            return response()->json([
                "series" => new Resources\SeriesResource($series),
                "sermons"=>Resources\SermonResource::collection($sermons)
            ], 200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $slug)
    {
        $series = Series::where('slug','=',$slug)->first();
        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            $series->update([
                "title"         =>  $request->title,
                "slug"          =>  Str::slug($request->title).date("-Y-m-d"),
                "description"   =>  Purifier::clean($request->description),
                "theme_id"      =>  $request->theme_id,
            ]);
            return response()->json(["series"=>new Resources\SeriesResource($series)],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash($slug)
    {
        $series = Series::where('slug','=',$slug)->first();
        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            $series->update([
                "first_sermon_date" =>null
            ]);
            if($series->sermons->count()>0){
                foreach ($series->sermons as $sermon){
                    $sermon->update([
                        'series_id'=>null
                    ]);
                }
            }
            $series->delete();
            return response()->json(["response" => true], 200);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($slug)
    {
        $series = Series::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            $series->restore();
            return response()->json(["response" => true], 200);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($slug)
    {
        $series = Series::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($series))
            return response()->json(["response"=>false],204);
        else {
            $series->forceDelete();
            return response()->json(["response" => true], 200);
        }
    }
}
