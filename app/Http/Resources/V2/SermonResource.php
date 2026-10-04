<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class SermonResource extends JsonResource
{
    /**
     * Unlike the legacy API (where /initiate serves the raw un-highlighted
     * body via the unversioned SermonResource, while /dashboard uses a
     * highlight-span body — an inconsistency that breaks caption/highlight
     * addressing depending on which endpoint served the sermon), V2 always
     * serves the highlight-span body, from the single canonical
     * Sermon::refactorBody() implementation.
     */
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "title" => $this->title,
            "slug" => $this->slug,
            "subtitle" => $this->subtitle,
            "videoUrl" => $this->video_url,
            "body" => $this->refactorBody(),
            "author" => new AuthorResource($this->author),
            "series" => $this->series ? new SeriesResource($this->series) : null,
            "category" => $this->category ? ["id" => $this->category->id, "name" => $this->category->name] : null,
            "publishedAt" => intval($this->published_at),
            "createdAt" => intval($this->created_at?->getTimestamp()),
            "updatedAt" => intval($this->updated_at?->getTimestamp()),
            "trashed" => $this->trashed(),
        ];
    }
}
