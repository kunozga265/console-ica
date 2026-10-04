<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "title" => $this->title,
            "slug" => $this->slug,
            "image" => $this->image,
            "venue" => $this->venue,
            "time" => $this->time,
            "duration" => $this->duration !== null ? floatval($this->duration) : null,
            "startDate" => intval($this->start_date),
            "endDate" => $this->end_date !== null ? intval($this->end_date) : null,
            "body" => $this->body,
        ];
    }
}
