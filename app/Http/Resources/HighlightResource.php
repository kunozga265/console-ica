<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HighlightResource extends JsonResource
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
            "id" => intval($this->id),
            "sermonId" => intval($this->sermon_id),
            "userId" => intval($this->user_id),
            "highlightId" => intval($this->highlight_id),
            "date" => intval($this->date),
        ];
    }
}
