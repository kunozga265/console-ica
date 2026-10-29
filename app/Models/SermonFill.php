<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SermonFill extends Model
{
    protected $fillable = ['user_id', 'sermon_id', 'fill_key', 'value'];
}
