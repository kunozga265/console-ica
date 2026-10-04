<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "sermonId" => intval($this->sermon_id),
            "userId" => intval($this->user_id),
            "body" => $this->body,
            "date" => intval($this->date),
        ];
    }
}
