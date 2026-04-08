<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
//        $date=date("m",$this->published_at);


//        $published_date=Carbon::createFromTimestamp($this->published_at);
        return [
            "id"            =>  intval($this->id),
            "title"         =>  $this->title,
            "slug"          =>  $this->slug,
            "subtitle"      =>  $this->subtitle,
            "video_url"     =>  $this->video_url,
            "body"          =>  $this->body,
            "author"        =>  new AuthorResource($this->author),
            "series"        =>  new SeriesResource($this->series),
            "category"      =>  $this->category,
            "published_at"  =>  intval($this->published_at),
            "published_date"  =>  [
                'day'   => date("d",$this->published_at),
                'month'   => date("M",$this->published_at),
                'year'   => date("Y",$this->published_at)
            ],
            "created_at"    =>  intval($this->created_at->getTimestamp()),
            "updated_at"    =>  intval($this->updated_at->getTimestamp()),
            "trashed"       =>  $this->trashed()
        ];
    }
}
