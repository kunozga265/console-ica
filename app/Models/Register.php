<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    public function members()
    {
        return $this->belongsToMany(Member::class,'member_register','register_id','member_id');
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }



    protected $fillable = [
        "code",
        "name",
        "ministry_id",
        "date",
    ];
}
