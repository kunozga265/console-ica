<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "member" => new MemberResource($this->member),
            "date" => $this->date !== null ? intval($this->date) : null,
        ];
    }
}
