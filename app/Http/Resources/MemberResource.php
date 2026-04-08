<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $birthday = Carbon::createFromTimestamp($this->date_of_birth);
        $now = Carbon::now();

        // Birthday in current year
        $nextBirthday = $birthday->copy()->year($now->year);

        // If already passed this year, move to next year
        if ($nextBirthday->isPast()) {
            $nextBirthday->addYear();
        }

        return [
            "id" => intval($this->id),
            "code" => $this->code,
            "avatar" => $this->avatar,
            "name" => $this->fullName(),
            "first_name" => $this->first_name,
            "middle_name" => $this->middle_name,
            "last_name" => $this->last_name,
            "fullName" => $this->first_name . " " . $this->last_name,
            "email" => $this->email,
            "gender" => $this->gender,
            "date_of_birth" => $this->date_of_birth != null ? intval($this->date_of_birth) : null,
            "nextBirthday" => $nextBirthday,
            "cell" => $this->cell?->name,
            "phone_number_airtel" => $this->phone_number_airtel,
            "phone_number_tnm" => $this->phone_number_tnm,
            "phone_number_international" => $this->phone_number_international,
        ];
    }
}
