<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable=[
        "image",
        "title",
        "slug",
        "venue",
        "time",
        "duration",
        "start_date",
        "end_date",
        "body",
    ];
}
