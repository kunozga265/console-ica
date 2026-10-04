<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => intval($this->id),
            "amount" => floatval($this->amount),
            "balance" => floatval($this->balance),
            "type" => $this->getType(),
            "description" => $this->description,
            "cell" => $this->cell?->name,
            "date" => intval($this->created_at?->getTimestamp()),
        ];
    }
}
