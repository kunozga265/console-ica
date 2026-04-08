<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeriesSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function toArray($request)
    {
        $latest_sermon=$this->sermons()->orderBy("published_at","desc")->limit(1)->get();
        $first_sermon=$this->sermons()->orderBy("published_at","asc")->limit(1)->get();

        $sermon_count=$this->sermons->count();

        switch ($sermon_count){
            case 0:
                $duration=null;
                break;
            case 1:
                $duration=date("M d, Y", $first_sermon[0]->published_at);
                break;
            default:
                $duration=date("M d, Y", $first_sermon[0]->published_at)." - ".date("M d, Y", $latest_sermon[0]->published_at);
        }

        return [
            'id'            =>  intval($this->id),
            "title"         =>  $this->title,
            "description"   =>  $this->description,
            "duration"      =>  $duration,
            "sermon_count"  =>  intval($this->sermons->count())
        ];
    }
}
