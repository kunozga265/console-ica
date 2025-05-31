<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        "date",
        "user_id",
        "sermon_id",
        "caption",
        "caption_id",
        "comment",];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];
}
