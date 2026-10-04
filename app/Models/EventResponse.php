<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A user's answer to "are you attending?" for an event. */
class EventResponse extends Model
{
    protected $fillable = ['event_id', 'user_id', 'attending'];

    protected $casts = ['attending' => 'boolean'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
