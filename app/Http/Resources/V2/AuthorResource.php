<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "avatar" => $this->avatar,
            "coverImage" => $this->cover_image,
            "name" => $this->name,
            "suffix" => $this->suffix,
            "title" => $this->title,
            "slug" => $this->slug,
            "icaPastor" => boolval($this->ica_pastor),
            "biography" => $this->biography,
            "sermonCount" => intval($this->sermons()->count()),
            "trashed" => $this->trashed(),
        ];
    }
}
