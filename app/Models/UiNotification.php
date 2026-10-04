<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * In-app alert for the /ui pages (top-bar bell). Kept separate from the
 * legacy `notifications` table, which is an unused placeholder.
 */
class UiNotification extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'url', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public static function send(iterable $userIds, string $title, ?string $body = null, ?string $url = null): void
    {
        foreach (collect($userIds)->filter()->unique() as $userId) {
            static::create(['user_id' => $userId, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
    }
}
