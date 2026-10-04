<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A whole sermon on a user's "saved" (bookmarked) or "favorite" list. */
class SermonSave extends Model
{
    public const KINDS = ['saved', 'favorite'];

    protected $fillable = ['sermon_id', 'user_id', 'kind'];

    public function sermon()
    {
        return $this->belongsTo(Sermon::class);
    }
}
