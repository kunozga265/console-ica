<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Register extends Model
{
    use HasFactory;

    public function getRouteKeyName()
    {
        return "code";
    }

    /**
     * Members who have an Attendance record against this register.
     *
     * This used to be a plain method returning a manually-queried Collection
     * (not a real Eloquent relation), which meant property access
     * ($register->members, used by RegisterResource and the API) would
     * throw at runtime since it wasn't a Relation instance. hasManyThrough
     * gives the same "members checked in to this register" result as a
     * genuine, query-able relation.
     */
    public function members()
    {
        return $this->hasManyThrough(
            Member::class,
            Attendance::class,
            'register_id', // Foreign key on attendances referencing registers
            'id', // Foreign key on members referencing members.id
            'id', // Local key on registers
            'member_id' // Local key on attendances referencing members
        )->orderBy('first_name', 'asc')->distinct();
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
