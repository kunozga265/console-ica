<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "code" => $this->code,
            "name" => $this->name,
            "ministry" => $this->ministry ? new MinistryResource($this->ministry) : null,
            "date" => intval($this->date),
            "active" => Carbon::createFromTimestamp($this->date)->isToday(),
            // Real hasManyThrough relation as of the Phase 2 fix — no longer
            // a plain Collection that throws on property access.
            "attendees" => MemberResource::collection($this->members),
            "checked" => $this->isAuthRegistered(),
        ];
    }
}
