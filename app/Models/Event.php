<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable=[
        "image",
        "title",
        "slug",
        "venue",
        "time",
        "duration",
        "start_date",
        "end_date",
        "body",
        "ministry_id",
    ];

    /** Timestamp of the event's last day (end_date is exclusive when it falls on midnight UTC). */
    public static function lastDayTimestamp(self $event): int
    {
        $end = intval($event->end_date ?: $event->start_date);

        return $end > $event->start_date && $end % 86400 === 0 ? $end - 86400 : $end;
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    /** Attending / not-attending responses (see EventResponse). */
    public function responses()
    {
        return $this->hasMany(EventResponse::class);
    }
}
