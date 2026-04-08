<?php

namespace App\Http\Resources\V1_3;

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
            "userId" => intval($this->user_id),
            "sermonId" => intval($this->sermon_id),
            "caption" => $this->caption,
            "captionId" => intval($this->caption_id),
            "comment" => $this->comment ?? ""
        ];
    }
}
