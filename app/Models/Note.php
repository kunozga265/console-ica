<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    public function sermon()
    {
        return $this->belongsTo(Sermon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        "sermon_id",
        "user_id",
        "body",
        "date",
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];
}
