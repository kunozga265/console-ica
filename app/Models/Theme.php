<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    protected $fillable=[
        'title',
        'year',
        'description',
        "slug"
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];

    public function series()
    {
        return $this->hasMany("App\Models\Series");
    }
}
