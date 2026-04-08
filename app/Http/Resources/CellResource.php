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
        $members = $this->members;
        $merged = $members->merge($this->leaders);
        $all = $merged->sortBy('first_name');



        return [
            "id"                => intval($this->id),
            "code"              => $this->code,
            "name"              => $this->name,
            "details"           => $this->details,
            "location"          => $this->location,
            "zone"              => $this->zone,
            "type"              => $this->getType(),
            // "leader"            => $this->user?->fullName() ?? "",
            "leader"            => $this->listOfLeaders(),
            "balance"           => floatval($this->balance),
            "members"           => MemberResource::collection($all),
            "meetings"          => $this->meetings != null ? MeetingResource::collection($this->meetings) : [],
            "transactions"      => $this->transactions != null ? TranscationResource::collection($this->transactions()->latest()->get()) : [],
            "next_meeting_date" => $this->nextMeetingDate(),
        ];
    }
}
