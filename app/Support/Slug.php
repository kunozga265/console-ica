<?php

namespace App\Support;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Slug
{
    /**
     * "<title>-<Y-m-d>" — the scheme the old CMS used (public URLs depend on it) —
     * made unique with a -2, -3… suffix, counting trashed rows too.
     */
    public static function unique(string $model, string $text): string
    {
        $base = Str::slug($text).date('-Y-m-d');
        $query = fn () => in_array(SoftDeletes::class, class_uses_recursive($model)) ? $model::withTrashed() : $model::query();

        $slug = $base;
        for ($i = 2; $query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
