<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Member extends Model
{
    use HasFactory;

    public function ministries()
    {
        return $this->belongsToMany(Ministry::class, "member_ministry");
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function fullName()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function isAdmin()
    {
        foreach($this->users as $user){
            if($user->hasRole('super')){
                return true;
            }
        }
        return false;
    }

    public function leadershipCell()
    {
        return $this->hasOne(Cell::class,"id","leader_cell_id");
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function registers()
    {
        return $this->belongsToMany(Role::class,'member_register','member_id','register_id');
    }

    public function attendanceCount()
    {
        if($this->attendances()->count() == 1){
            return $this->attendances()->count() ." Meeting";
        }else{
            return $this->attendances()->count() ." Meetings";
        }
    }

    protected $fillable = [
        "code",
        "avatar",
        "first_name",
        "middle_name",
        "last_name",
        "gender",
        "cell_id",
        "leader_cell_id",
        "date_of_birth",
        "phone_number_airtel",
        "phone_number_tnm",
        "phone_number_international",
        "email",
        "associated",
    ];

}
