<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrayerResource extends JsonResource
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
            'date'          =>  intval($this->date),
            'title'         =>  $this->title,
            'verses'        =>  $this->verses,
            'body'          =>  $this->body,
        ];
    }
}
