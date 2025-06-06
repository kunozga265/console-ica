<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class,'user_role','role_id','user_id');
    }

    protected $fillable=[
        "name"
    ];

    protected $hidden=[
        'pivot',
        "created_at",
        "updated_at",
    ];
}
