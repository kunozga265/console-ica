<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Author extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    public function sermons()
    {
        return $this->hasMany("App\Models\Sermon");
    }

    public function searchableAs()
    {
        return "authors_index";
    }

    protected $fillable=[
        "avatar",
        "cover_image",
        "name",
        "suffix",
        "title",
        "biography",
        "ica_pastor",
        "slug"
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];
}
