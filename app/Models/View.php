<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;

    public function sermon()
    {
        return $this->hasOne("App\Models\Sermon","id","sermon_id");
    }

    protected $fillable=[
        "sermon_id",
        "count"
    ];

    protected $hidden=[
        "created_at",
        "updated_at"
    ];
}
