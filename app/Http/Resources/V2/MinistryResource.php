<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class MinistryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "name" => $this->name,
            "slug" => $this->slug,
        ];
    }
}
