<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "code" => $this->code,
            "avatar" => $this->avatar,
            "firstName" => $this->first_name,
            "middleName" => $this->middle_name,
            "lastName" => $this->last_name,
            "email" => $this->email,
            "gender" => $this->gender,
            "dateOfBirth" => $this->date_of_birth,
            "cell" => $this->cell != null ? [
                "id"                => intval($this->cell->id),
                "code"              => $this->cell->code,
                "name"              => $this->cell->name,
                "zone"              => $this->cell->zone,
                "type"              => $this->cell->getType(),
                "leader"            => $this->cell->user != null ? $this->cell->user->fullName() : "",
            ] : null,
            "phoneNumberAirtel" => $this->phone_number_airtel,
            "phoneNumberTnm" => $this->phone_number_tnm,
            "phoneNumberInternational" => $this->phone_number_international,
        ];
    }
}
