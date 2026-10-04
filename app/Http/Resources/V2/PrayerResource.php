<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class PrayerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "title" => $this->title,
            "date" => intval($this->date),
            "verses" => $this->verses,
            "body" => $this->body,
        ];
    }
}
