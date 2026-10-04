<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class DownloadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "title" => $this->title,
            "slug" => $this->slug,
            "type" => $this->type,
            "path" => $this->path,
            "date" => intval($this->date),
            "description" => $this->description,
        ];
    }
}
