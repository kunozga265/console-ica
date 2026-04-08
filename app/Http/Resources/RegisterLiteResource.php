<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterLiteResource extends JsonResource
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
            "ministry"          => $this->ministry,
            "date"              => intval($this->date),
            "active"            => Carbon::createFromTimestamp($this->date)->isToday(),
            "attendees"         => [],
            "checked"           => $this->isAuthRegistered()
        ];
    }
}
