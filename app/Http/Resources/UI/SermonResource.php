<?php

namespace App\Http\Resources\UI;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sermon in the shape the /ui pages use (camelCase, epoch milliseconds);
 * see resources/js/Components/UI/sampleData.js.
 */
class SermonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => intval($this->id),
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'videoUrl'    => $this->video_url,
            // Only when selected — list queries leave the (large) body out.
            'body'        => $this->when(array_key_exists('body', $this->resource->getAttributes()), fn () => $this->body),
            'publishedAt' => intval($this->published_at) * 1000,
            'author'      => $this->author ? [
                'id'     => intval($this->author->id),
                'name'   => $this->author->name,
                'suffix' => trim((string) $this->author->suffix),
                'title'  => $this->author->title,
                'avatar' => $this->author->avatar,
            ] : null,
            'ministry'    => $this->whenLoaded('ministry', fn () => $this->ministry ? ['id' => intval($this->ministry->id), 'name' => $this->ministry->name] : null),
            'series'      => $this->series ? [
                'id'    => intval($this->series->id),
                'title' => $this->series->title,
            ] : null,
        ];
    }
}
