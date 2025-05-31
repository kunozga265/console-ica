<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable=[
        "name",
        "leaders",
        "location"
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
        "location",
    ];
}
