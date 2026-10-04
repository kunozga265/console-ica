<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "code" => $this->code,
            "avatar" => $this->avatar,
            "name" => $this->fullName(),
            "firstName" => $this->first_name,
            "middleName" => $this->middle_name,
            "lastName" => $this->last_name,
            "email" => $this->email,
            "gender" => $this->gender,
            "dateOfBirth" => $this->date_of_birth !== null ? intval($this->date_of_birth) : null,
            "cell" => $this->cell ? ["id" => $this->cell->id, "name" => $this->cell->name] : null,
            "phoneNumberAirtel" => $this->phone_number_airtel,
            "phoneNumberTnm" => $this->phone_number_tnm,
            "phoneNumberInternational" => $this->phone_number_international,
        ];
    }
}
