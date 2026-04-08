<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
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
            "date" => intval($this->date),
            "user_id" => intval($this->user_id),
            "sermon_id" => intval($this->sermon_id),
            "caption" => $this->caption,
            "caption_id" => intval($this->caption_id),
            "comment" => $this->comment ?? ""
        ];
    }
}
