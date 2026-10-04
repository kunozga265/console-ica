<?php

namespace App\Http\Controllers\API\V1_0_0;

use App\Http\Controllers\Controller;
use App\Http\Resources;
use App\Models\Author;
use App\Models\Sermon;
use App\Models\Series;
use App\Models\View;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Validator;

class SermonController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  string  $query
     * @return \Illuminate\Http\JsonResponse
     */
    public function search($query){
        $sermons=Sermon::where('title', 'like', '%' .$query. '%')->orderBy('title','asc')->paginate((new AppController())->paginate);
//     $series=Series::search($query)->get();
//     return response()->json([
//       "sermons" => Resources\SermonResource::collection($sermons),
//       "series" => Resources\SeriesSearchResource::collection($series)
//     ],200);
        return response()->json(new Resources\SermonCollection($sermons),200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $sermons= Sermon::orderBy("published_at","desc")->paginate((new AppController())->paginate);
        return response()->json(new Resources\SermonCollection($sermons),200);
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

            return response()->json(Resources\SermonResource::collection($sermons), 200);
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
            $sermons= Author::where('slug',$slug)->first()->sermons()->orderBy("published_at","desc")->paginate((new AppController())->paginate);
            return response()->json(new Resources\SermonCollection($sermons), 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $timestamp
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatest($timestamp)
    {
        //if timestamp is zero get the latest sermon
        if ($timestamp==0) {
            $sermons = Sermon::where("published_at", "<=", Carbon::now()->getTimestamp())->orderBy("published_at","desc")->limit(1)->get();
//            $sermons = Sermon::all();
//            dd($sermons);
        }

        //else query from the last updated timestamp
        else{
            //formatting timestamp to query db
            //$formatted_timestamp = date("Y-n-d H:i:s",$timestamp);
            $sermons = Sermon::where("published_at",">",$timestamp)->get();
        }

        if ($sermons->count()==0)
            return response()->json(["response"=>false],204);
        else
            return response()->json(Resources\SermonResource::collection($sermons),200);
    }

    /**
     * Display a listing of the resource.
     * @param $author_id
     * @param $category
     * @param $sort
     * @param $fromDate
     * * @param $endDate
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSermons($author_id, $category, $sort, $fromDate, $endDate)
    {
        $pagination_items=(new AppController())->paginate;
        if($author_id==0){
            switch ($sort){
                case "TITLE_ASC":
                    $sermons= Sermon::where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("title","asc")->paginate($pagination_items);
                    break;
                case "TITLE_DESC":
                    $sermons= Sermon::where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("title","desc")->paginate($pagination_items);
                    break;
                case "DATE_ASC":
                    $sermons= Sermon::where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("published_at","asc")->paginate($pagination_items);
                    break;
                case "DATE_DESC":
                    $sermons= Sermon::where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("published_at","desc")->paginate($pagination_items);
                    break;
                default:
                    $sermons=[];
            }

        }else{
            switch ($sort){
                case "TITLE_ASC":
                    $sermons= Sermon::where("author_id",$author_id)->where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("title","asc")->paginate($pagination_items);
                    break;
                case "TITLE_DESC":
                    $sermons= Sermon::where("author_id",$author_id)->where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("title","desc")->paginate($pagination_items);
                    break;
                case "DATE_ASC":
                    $sermons= Sermon::where("author_id",$author_id)->where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("published_at","asc")->paginate($pagination_items);
                    break;
                case "DATE_DESC":
                    $sermons= Sermon::where("author_id",$author_id)->where("published_at","<=",$fromDate)->where("published_at",">=",$endDate)->orderBy("published_at","desc")->paginate($pagination_items);
                    break;
                default:
                    $sermons=[];
            }
        }

        return response()->json(new Resources\SermonCollection($sermons),200);
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
                $sermons= Sermon::where("published_at", "<=", Carbon::now()->getTimestamp())->orderBy("published_at","desc")->paginate((new AppController())->paginate);
                break;
            case "Scheduled":
                $sermons= Sermon::where("published_at", ">", Carbon::now()->getTimestamp())->orderBy("published_at","desc")->paginate((new AppController())->paginate);
                break;
            case "Trashed":
                $sermons=Sermon::onlyTrashed()->orderBy("published_at","asc")->paginate((new AppController())->paginate);
                break;
            case "Search":
                $sermons=Sermon::search($query)->withTrashed()->paginate((new AppController())->paginate);
                break;
            case "Views":
                $views=View::orderBy("count","desc")->paginate((new AppController())->paginate);
                return response()->json(new Resources\ViewCollection($views),200);
                break;
            default:
                return response()->json([],204);
        }
        return response()->json(new Resources\SermonCollection($sermons),200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduled()
    {
        $sermons= Sermon::where("published_at", ">", Carbon::now()->getTimestamp())->orderBy("published_at","asc")->get();
        return response()->json(Resources\SermonResource::collection($sermons),200);
    }
//    /**
//     * Display a listing of the resource.
//     *
//     * @return \Illuminate\Http\JsonResponse
//     */
//    public function getViews()
//    {
//        $views=View::orderBy("count","desc")->paginate(2);
//        return response()->json(new Resources\ViewCollection($views),200);
//    }

    public function create()
    {
        return view('');
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
            "body"      =>  "required",
            "author_id" =>  "required",
        ]);

        if ($validator->fails()){
            return response()->json(["message"=>"Title, body and author_id attributes required"],400);
        }

        $body=$request->body;
        str_replace("\n","",$body);
        str_replace("\t","",$body);

        $sermon=new Sermon([
            "title"         =>  $request->title,
            "slug"          =>  Str::slug($request->title).date("-Y-m-d"),
            "subtitle"      =>  $request->subtitle,
            "video_url"     =>  $request->video_url,
            "body"          =>  $body,
            "author_id"     =>  $request->author_id,
            "series_id"     =>  $request->series_id,
            "category_id"   =>  $request->category_id,
            "published_at"  =>  $request->published_at != null? $request->published_at : Carbon::now()->getTimestamp()
        ]);

        $sermon->save();
        $view=new View([
            "sermon_id"  =>  $sermon->id,
            "count"      =>  0
        ]);
        $view->save();

        if($sermon->series !==null) {
            $series = Series::find($sermon->series->id);

            if ($series->first_sermon_date == null || $series->first_sermon_date > $sermon->published_at) {
                $series->update([
                    "first_sermon_date" => $sermon->published_at
                ]);
            }
        }

        $author=$sermon->author->suffix." ".$sermon->author->name;
        $this->pushNotification('general',$sermon->title,$author);

        return response()->json(["sermon"=>new Resources\SermonResource($sermon)],200);
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
            return response()->json(["response"=>false],204);
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

            return response()->json([
                "sermon" => new Resources\SermonResource($sermon),
//                "sermonSeries"=> Resources\SermonResource::collection($sermonSeries)
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
        $sermon = Sermon::where('slug','=',$slug)->first();

        if (!is_object($sermon))
            return response()->json(["response"=>false],204);
        else {
            $existentSeries=$sermon->series_id;
            $sermon->update([
                "title"         =>  $request->title,
                "slug"          =>  Str::slug($request->title).date("-Y-m-d"),
                "subtitle"      =>  $request->subtitle,
                "video_url"     =>  $request->video_url,
                "body"          =>  Purifier::clean($request->body),
                "author_id"     =>  $request->author_id,
                "series_id"     =>  $request->series_id,
                "category_id"   =>  $request->category_id,
                "published_at"  =>  $request->published_at
            ]);

            if(isset($existentSeries)){
                $this->setSeriesFirstSermonDate($existentSeries);
            }

            if($request->series_id && $request->series_id!=$existentSeries){
                $this->setSeriesFirstSermonDate($request->series_id);
            }

//
//            if($sermon->series !==null) {
//                $series = Series::find($sermon->series->id);
//
//                if ($series->first_sermon_date == null || $series->first_sermon_date > $sermon->published_at) {
//                    $series->update([
//                        "first_sermon_date" => $sermon->published_at
//                    ]);
//                }
//            }elseif ($sermon->series == null && $existentSeries!=null){
//                $series = Series::find($existentSeries);
//                if ($series->count()==0){
//                    $series->update([
//                        "first_sermon_date" => 0
//                    ]);
//                }else{
//                    $sermons=$series->sermons()->orderBy("published_at","asc")->limit(1)->get();
//                    $series->update([
//                        "first_sermon_date" =>$sermons->published_at
//                    ]);
//                }
//            }

            return response()->json(["sermon"=>new Resources\SermonResource($sermon)],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function trash($slug)
    {
        $sermon = Sermon::where('slug','=',$slug)->first();
        if (!is_object($sermon))
            return response()->json(["response" => false], 204);
        else {
            $sermon->delete();
            if($sermon->series !==null) {
                $this->setSeriesFirstSermonDate($sermon->series->id);
            }
            return response()->json(["response" => true], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($slug)
    {
        $sermon = Sermon::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($sermon))
            return response()->json(["response"=>false],204);
        else {
            $sermon->restore();
            if($sermon->series !==null) {
                $this->setSeriesFirstSermonDate($sermon->series->id);
            }
            return response()->json(["response" => true], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($slug)
    {
        $sermon = Sermon::onlyTrashed()->where('slug','=',$slug)->first();
        if (!is_object($sermon))
            return response()->json(["response"=>false],204);
        else {
            $sermon->forceDelete();
            return response()->json(["response" => true], 200);
        }
    }

    private function setSeriesFirstSermonDate($seriesId){
        $series = Series::find($seriesId);
        $sermons=$series->sermons()->orderBy("published_at","asc")->get();

        if($sermons->isNotEmpty()){
            $series->update([
                "first_sermon_date" =>$sermons->first()->published_at
            ]);
        }else{
            $series->update([
                "first_sermon_date" =>null
            ]);
        }
    }

    public function uploadImage(Request $request)
    {

        try {
//            $request->validate([
//                'image'=>'mimes:jpeg,png'
//            ]);

            $extension=$request->image->extension();
            $filename=uniqid().".".$extension;
            $request->image->move(public_path("images/sermons"),$filename);

            return response()->json(['url'=>"https://ica.ovationadagency.com/images/sermons/$filename"],200);

        }catch (\RuntimeException $e){
            return response()->json([
                'error' => "The image upload failed",
            ],501);
        }
    }

    private function pushNotification($title,$subject,$message){
        //notification
        try{
            $client=new Client();
            $to=str_replace(' ','',$title);
            $notificationRequest=$client->request('POST','https://fcm.googleapis.com/fcm/send',[
                'headers'=>[
                    'Authorization' => 'key=AAAAQdj1ZOU:APA91bHbQ6JbhcEoHTyQthEp1j8QjlDUM7ftsFmcMRUvgKuZJBy5-IQQ_6eZZAfJ5fUM1qP60dATN-DiOzM3LcUnjcjR7-vGzE02iC7jCEuJU3GC_qrLXcxyY6P7zy57joaqbytyWj59',
                    'Content-Type'   =>  'application/json',
                ],
                'json'=>[
                    "priority"=>"high",
                    "content_available"=>true,
                    "to"=>"/topics/$to",
                    "notification"=>[
                        "title"=>$subject,
                        "body"=>$message
                    ]
                ]
            ]);

            // Develop a use for this
            if ($notificationRequest->getStatusCode()==200){}


        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            //Log information
        }
    }
}
