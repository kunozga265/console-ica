<?php

namespace App\Http\Resources\UI;

use Illuminate\Http\Resources\Json\JsonResource;

/** Minister for the /ui pages. Expects a `sermons_count` from withCount(). */
class AuthorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => intval($this->id),
            'name'        => $this->name,
            'suffix'      => trim((string) $this->suffix),
            'title'       => $this->title,
            'slug'        => $this->slug,
            'avatar'      => $this->avatar,
            'biography'   => $this->biography,
            'sermonCount' => intval($this->sermons_count),
        ];
    }
}
