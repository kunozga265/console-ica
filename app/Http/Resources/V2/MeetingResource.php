<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "code" => $this->code,
            "date" => intval($this->date),
            "venue" => $this->venue,
            "cell" => $this->cell?->name,
            "offering" => $this->offering !== null ? floatval($this->offering) : null,
            "attendances" => AttendanceResource::collection($this->attendances),
        ];
    }
}
