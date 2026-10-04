<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "sermonId" => intval($this->sermon_id),
            "userId" => intval($this->user_id),
            "captionId" => intval($this->caption_id),
            "caption" => $this->caption,
            "comment" => $this->comment,
            "date" => intval($this->date),
        ];
    }
}
