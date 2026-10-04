<?php

namespace App\Http\Controllers\API\V1_1;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Event;
use App\Models\Page;
use App\Models\Prayer;
use App\Models\Series;
use App\Models\Sermon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources;
use Mews\Purifier\Facades\Purifier;

class AppController extends Controller
{
    public $paginate=20;

    public function initiate()
    {
        //get prayer points
        $now=Carbon::now()->getTimestamp();
        $prayers=Prayer::where('date','<=',$now)->orderBy('date','desc')->limit((new AppController())->paginate)->get();

        $sermons= Sermon::orderBy("published_at","desc")->limit((new AppController())->paginate)->get();
        $series= Series::where("first_sermon_date","!=",null)->orderBy("first_sermon_date","desc")->limit((new AppController())->paginate)->get();
        $events= Event::orderBy("start_date","desc")->limit((new AppController())->paginate)->get();
        $authors= Author::all();

        return response()->json([
            'sermons'   => Resources\SermonResource::collection($sermons),
            'series'    => Resources\SeriesResource::collection($series),
            'authors'   => Resources\AuthorResource::collection($authors),
            'prayer_points'   => Resources\PrayerResource::collection($prayers),
            'events'    => Resources\EventResource::collection($events)
        ]);
    }

    public function dashboard($timestamp)
    {
        if(!isset($timestamp)){
            return response()->json([
                "message" => "Timestamp is required."
            ],400);
        }

        //get prayer points
//        $now=Carbon::now()->getTimestamp(collection);
        if($timestamp == 0){
            $now=Carbon::now()->getTimestamp();
            $prayers=Prayer::where('date','<=',$now)->orderBy('date','desc')->limit((new AppController())->paginate)->get();
            $sermons= Sermon::orderBy("published_at","desc")->limit((new AppController())->paginate)->get();
            $series= Series::where("first_sermon_date","!=",null)->orderBy("first_sermon_date","desc")->limit((new AppController())->paginate)->get();
            $events= Event::orderBy("start_date","desc")->limit((new AppController())->paginate)->get();
            $authors= Author::all();
        }
        else{
            $prayers = Prayer::where('date', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy('date', 'desc')->limit((new AppController())->paginate)->get();
            $sermons = Sermon::where('published_at', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy("published_at", "desc")->limit((new AppController())->paginate)->get();
            $series = Series::where('updated_at', '>', Carbon::createFromTimestamp($timestamp))->where("first_sermon_date", "!=", null)->orderBy("first_sermon_date", "desc")->limit((new AppController())->paginate)->get();
            $events = Event::where('start_date', '>', $timestamp)->orWhere('updated_at', '>', Carbon::createFromTimestamp($timestamp))->orderBy("start_date", "desc")->limit((new AppController())->paginate)->get();
            $authors = Author::where('updated_at', '>', Carbon::createFromTimestamp($timestamp))->get();
        }

        $announcements = Page::where("name","announcements")->first();
        $fundraising = Page::where("name","fundraising")->first();

        return response()->json([
            'sermons'   => Resources\SermonResource::collection($sermons),
            'series'    => Resources\SeriesResource::collection($series),
            'authors'   => Resources\AuthorResource::collection($authors),
            'prayer_points'   => Resources\PrayerResource::collection($prayers),
            'events'    => Resources\EventResource::collection($events),
            'announcements'    => new Resources\PageResource($announcements),
            'fundraising'    => new Resources\PageResource($fundraising),

        ]);
    }

    public function search($query)
    {
        $sermons=Sermon::where('title', 'like', '%' .$query. '%')->orderBy('title','asc')->limit(20)->get();
        $series=Series::where('title', 'like', '%' .$query. '%')->orderBy('title','asc')->limit(20)->get();
        $authors=Author::where('name', 'like', '%' .$query. '%')->orderBy('name','asc')->paginate((new AppController())->paginate);

        return response()->json([
            'sermons'   => Resources\SermonResource::collection($sermons),
            'series'    => Resources\SeriesResource::collection($series),
            'authors'   => Resources\AuthorResource::collection($authors),
        ]);
    }

    public function seeder(Request $request)
    {
        foreach ($request->sermons as $sermon){
            Sermon::create([
                "title"             =>  $sermon["title"],
                "slug"              =>  $sermon["slug"],
                "subtitle"          =>  $sermon["subtitle"],
                "video_url"         =>  $sermon["video_url"],
                "body"              =>  Purifier::clean($this->filterBody($sermon["body"])),
                "author_id"         =>  $sermon["author"]["id"],
                "series_id"         =>  $sermon["series"]?$sermon["series"]["id"]:null,
                "published_at"      =>  $sermon["published_at"]
            ]);
        }

        foreach ($request->series as $series) {
            Series::create([
                "title"                 =>  $series["title"],
                "slug"                  =>  $series["slug"],
                "description"           =>  $series["description"],
                "first_sermon_date"     =>  $series["first_sermon_date"],
            ]);
        }

        foreach ($request->authors as $author) {
            Author::create([
                "avatar"            =>  $author["avatar"],
                "name"              =>  $author["name"],
                "suffix"            =>  $author["suffix"],
                "slug"              =>  $author["slug"],
                "title"             =>  $author["title"],
                "biography"         =>  $author["biography"],
                "ica_pastor"        =>  $author["ica_pastor"],
            ]);
        }
        foreach ($request->prayers as $prayer) {
            Prayer::create([
                'title'             => $prayer["title"],
                'date'              => $prayer["date"],
                'verses'            => $prayer["verses"],
                'body'              => $prayer["body"]
            ]);
        }

        dd("DB Seeded");
    }

    private function filterBody($body)
    {
        $body=json_decode(str_replace('\r\n\t<li>','<li>',json_encode($body)));
        $body=json_decode(str_replace('\r\n\t\t<li>','<li>',json_encode($body)));
        $body=json_decode(str_replace('<li>\r\n\t<p>','<li>',json_encode($body)));
        $body=json_decode(str_replace('<\/p>\r\n\r\n\t<ul>','\r\n\t<ul>',json_encode($body)));
        $body=json_decode(str_replace('<\/p>\r\n\r\n\t<ol>','\r\n\t<ol>',json_encode($body)));
        $body=json_decode(str_replace('<\/ul>\r\n\t<\/li>','<\/ul><\/li>',json_encode($body)));
        $body=json_decode(str_replace('<\/ol>\r\n\t<\/li>','<\/ol><\/li>',json_encode($body)));
        $body=json_decode(str_replace('<\/li>\r\n\t<\/ul>','<\/li><\/ul>',json_encode($body)));
        $body=json_decode(str_replace('<\/li>\r\n\t<\/ol>','<\/li><\/ol>',json_encode($body)));

        return $body;

    }

}
