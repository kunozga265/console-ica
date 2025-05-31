<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prayer extends Model
{
    use HasFactory;

    protected $fillable=[
        'title',
        'date',
        'verses',
        'body',
    ];

    protected $hidden=[
        'created_at',
        'updated_at',
    ];
}
