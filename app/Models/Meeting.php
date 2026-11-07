<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    /** A newly scheduled (today or later) meeting is announced to the cell. */
    protected static function booted(): void
    {
        static::created(function (Meeting $meeting) {
            if (intval($meeting->date) >= \Carbon\Carbon::today(config('app.timezone'))->getTimestamp()) {
                \App\Support\CellNotices::meetingScheduled($meeting);
            }
        });
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    protected $fillable = [
        "code",
        "date",
        "venue",
        "cell_id",
        "offering",
    ];
}
