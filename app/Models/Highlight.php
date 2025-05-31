<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    use HasFactory;

    protected $fillable = [
        "sermon_id",
        "highlight_id",
        "date",
        "user_id",
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];
}
