<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public function getType()
    {
        return $this->type ?  "Expense" : "Deposit";
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    protected $fillable=[
        "amount",
        "code",
        "type",
        "description",
        "meeting_id",
        "cell_id",
        "balance",
    ];
}
