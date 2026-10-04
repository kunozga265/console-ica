<?php

namespace App\Http\Resources\UI;

use Illuminate\Http\Resources\Json\JsonResource;

/** Prayer point for the /ui pages: date · title · optional verses. */
class PrayerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'     => intval($this->id),
            'date'   => intval($this->date) * 1000,
            'title'  => $this->title,
            'verses' => $this->verses,
        ];
    }
}
