<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prayer extends Model
{
    /** Users who said "I'm praying" for this prayer point. */
    public function prayingUsers()
    {
        return $this->belongsToMany(User::class, 'prayer_user')->withTimestamps();
    }

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
