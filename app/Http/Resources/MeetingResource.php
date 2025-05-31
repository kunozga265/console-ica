<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
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
            "id" => intval($this->id),
            "code" => $this->code,
            "date" => intval($this->date),
            "venue" => $this->venue,
            "cell" => $this->cell->name,
            "offering" => floatval($this->offering),
            "attendances" => AttendanceResource::collection($this->attendances),
        ];
    }
}
