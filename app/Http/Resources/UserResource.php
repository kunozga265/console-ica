<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'id' => intval($this->id),
            'avatar' => $this->avatar,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'other_name' => $this->other_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->roles()->first(),
            'cell_code' => $this->member?->leadershipCell?->code,
            'member_cell_code' => $this->member?->cell?->code
        ];
    }
}
