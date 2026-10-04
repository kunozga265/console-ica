<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class HighlightResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "sermonId" => intval($this->sermon_id),
            "userId" => intval($this->user_id),
            "highlightId" => intval($this->highlight_id),
            "date" => intval($this->date),
        ];
    }
}
