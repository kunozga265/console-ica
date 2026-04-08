<?php

namespace App\Http\Resources\V1_1;

use App\Http\Resources\AuthorResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AuthorCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data'      =>  AuthorResource::collection($this->collection),
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
