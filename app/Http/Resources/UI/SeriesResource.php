<?php

namespace App\Http\Resources\UI;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Series for the /ui pages. Expects `sermons_count`, `first_published` and
 * `last_published` from withCount()/withMin()/withMax() — one query for the
 * whole list instead of SeriesResource's three per series.
 */
class SeriesResource extends JsonResource
{
    public function toArray($request): array
    {
        $first = intval($this->first_published);
        $last = intval($this->last_published);
        $weeks = $this->sermons_count > 1 ? intdiv($last - $first, 7 * 24 * 3600) + 1 : null;

        return [
            'id'              => intval($this->id),
            'title'           => $this->title,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'duration'        => $weeks ? $weeks . ' ' . str('week')->plural($weeks) : null,
            'sermonCount'     => intval($this->sermons_count),
            'firstSermonDate' => $first * 1000,
        ];
    }
}
