<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

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
