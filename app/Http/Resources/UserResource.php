<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => intval($this->id),
            'avatar' => $this->avatar,
            'firstName' => $this->first_name,
            'middleName' => $this->middle_name,
            'otherName' => $this->other_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'role' => $this->roles()->first(),
            'cell_code' => $this->cell?->code,
            'isAdmin' => $this->hasRole('admin')
        ];
    }
}
