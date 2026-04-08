<?php

namespace App\Http\Resources\V1_1;

use App\Http\Resources\SermonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SermonCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data'      =>  SermonResource::collection($this->collection),
            'meta'      =>  [
                'current_page'      =>  $this->currentPage(),
                'total'             =>  $this->total(),
//                'per_page'          =>  $this->perPage(),
                'count'             =>  $this->count(),
                'has_more_pages'    =>  $this->hasMorePages(),
                'last_page'         =>  $this->lastPage()
            ]
        ];
    }
}
