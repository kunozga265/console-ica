<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"            => intval($this->id),
            "code"            => $this->code,
            "name"            => $this->name,
            "ministry"         => $this->ministry,
            "date"            => $this->date,
            "active"            => Carbon::createFromTimestamp($this->date)->isToday(),
            "attendees"         => MemberResource::collection($this->members),
        ];
    }
}
