<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "avatar" => $this->avatar,
            "firstName" => $this->first_name,
            "middleName" => $this->middle_name,
            "lastName" => $this->last_name,
            "email" => $this->email,
            "role" => $this->roles->first()?->name,
            "memberId" => $this->member_id !== null ? intval($this->member_id) : null,
            "cellCode" => $this->member?->leadershipCell?->code,
            "memberCellCode" => $this->member?->cell?->code,
        ];
    }
}
