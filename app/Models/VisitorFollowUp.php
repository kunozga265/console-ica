<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorFollowUp extends Model
{
    protected $fillable = ['member_id', 'user_id', 'date', 'comment', 'next_date'];

    protected $casts = ['date' => 'date', 'next_date' => 'date'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
