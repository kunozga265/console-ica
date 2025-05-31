<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TranscationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "amount" => floatval($this->amount),
            "balance" => floatval($this->balance),
            "type" => $this->getType(),
            "description" => $this->description,
            "cell" => $this->cell->name,
            "date" => $this->created_at->getTimestamp(),
        ];
    }
}
