<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'            =>  intval($this->id),
            'start_date'    =>  intval($this->start_date),
            'end_date'      =>  intval($this->end_date),
            'duration'      =>  intval($this->duration),
            'title'         =>  $this->title,
            'image'         =>  $this->image,
            'venue'         =>  $this->venue,
            'time'          =>  $this->time,
            'slug'          =>  $this->slug,
            'body'          =>  $this->body,
        ];
    }
}
