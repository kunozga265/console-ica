<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class CellResource extends JsonResource
{
    public function toArray($request)
    {
        $all = $this->members->merge($this->leaders)->sortBy("first_name")->values();

        return [
            "id" => intval($this->id),
            "code" => $this->code,
            "name" => $this->name,
            "details" => $this->details,
            "location" => $this->location,
            "zone" => $this->zone ? ["id" => $this->zone->id, "name" => $this->zone->name] : null,
            "type" => $this->getType(),
            "leader" => $this->listOfLeaders(),
            "balance" => floatval($this->balance),
            "verified" => boolval($this->verified),
            "members" => MemberResource::collection($all),
            "meetings" => MeetingResource::collection($this->meetings),
            "transactions" => TransactionResource::collection($this->transactions()->latest()->get()),
            "nextMeetingDate" => $this->nextMeetingDate(),
        ];
    }
}
