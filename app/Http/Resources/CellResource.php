<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CellResource extends JsonResource
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
            "id"                => intval($this->id),
            "code"              => $this->code,
            "name"              => $this->name,
            "details"           => $this->details,
            "location"          => $this->location,
            "zone"              => $this->zone,
            "type"              => $this->getType(),
            "leader"            => $this->user->fullName(),
            "balance"           => floatval($this->balance),
            "members"           => $this->members != null ? MemberResource::collection($this->members) : [],
            "meetings"          => $this->meetings != null ? MeetingResource::collection($this->meetings) : [],
            "transactions"      => $this->transactions != null ? TranscationResource::collection($this->transactions()->latest()->get()) : [],
            "next_meeting_date" => $this->nextMeetingDate(),
        ];
    }
}
