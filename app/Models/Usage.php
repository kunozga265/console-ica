<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    use HasFactory;

     public function users()
    {
        return $this->belongsToMany(User::class, 'usage_user', 'usage_id', 'user_id');
    }

    protected $fillable = [
        'date',
        'count',
    ];
}
