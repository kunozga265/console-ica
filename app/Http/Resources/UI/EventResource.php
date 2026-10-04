<?php

namespace App\Http\Resources\UI;

use Illuminate\Http\Resources\Json\JsonResource;

/** Event for the /ui pages. */
class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => intval($this->id),
            'title'   => $this->title,
            'date'    => intval($this->start_date) * 1000,
            'endDate' => intval($this->end_date) * 1000,
            'time'    => $this->time,
            'loc'     => $this->venue,
            'image'   => $this->image,
            'slug'    => $this->slug,
        ];
    }
}
