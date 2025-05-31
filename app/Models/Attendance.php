<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    protected $fillable=[
        "member_id",
        "meeting_id",
        "zone_id"
    ];
}
