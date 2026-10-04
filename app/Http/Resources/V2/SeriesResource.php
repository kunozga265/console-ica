<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    public function toArray($request)
    {
        $sermonCount = $this->sermons()->count();

        return [
            "id" => intval($this->id),
            "title" => $this->title,
            "slug" => $this->slug,
            "description" => $this->description,
            "theme" => $this->theme ? ["id" => $this->theme->id, "title" => $this->theme->title, "year" => $this->theme->year] : null,
            "sermonCount" => intval($sermonCount),
            "firstSermonDate" => $this->first_sermon_date ? intval($this->first_sermon_date) : null,
            "trashed" => $this->trashed(),
        ];
    }
}
