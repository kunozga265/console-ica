<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Register extends Model
{
    use HasFactory;

    public function members()
    {
        // return $this->belongsToMany(Member::class, 'member_register', 'register_id', 'member_id');

        $id = $this->id;
        return Member::whereHas('attendances', function ($query) use ($id) {
            $query->where('register_id',  $id);
        })->orderBy('first_name', 'asc')->get();
    }



    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }


    public function isAuthRegistered()
    {
        $user = User::find(Auth::id());
        if (is_object($user) && $user?->member != null) {
            return Attendance::where('register_id', $this->id)->where('member_id', $user?->member?->id)->exists();
        } else {
            return false;
        }
    }



    protected $fillable = [
        "code",
        "name",
        "ministry_id",
        "date",

    ];
}
