<?php

namespace App\Support;

use App\Models\Bookmark;
use App\Models\Highlight;
use App\Models\Note;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Writes for the app's sync endpoints (API 1.2 / 1.3 / 1.4). Always scoped to
 * the signed-in user and idempotent: one note per sermon, one bookmark and one
 * highlight per sentence (enforced by unique indexes as well).
 */
class AnnotationSync
{
    public static function highlight(int $userId, $sermonId, $spanId, $date): void
    {
        self::quietly(fn () => Highlight::firstOrCreate(
            ['user_id' => $userId, 'sermon_id' => $sermonId, 'highlight_id' => $spanId],
            ['date' => $date],
        ));
    }

    public static function unhighlight(int $userId, $sermonId, $spanId): void
    {
        Highlight::where(['user_id' => $userId, 'sermon_id' => $sermonId, 'highlight_id' => $spanId])->delete();
    }

    public static function bookmark(int $userId, $sermonId, $captionId, ?string $caption, $date, ?string $comment): void
    {
        self::quietly(fn () => Bookmark::updateOrCreate(
            ['user_id' => $userId, 'sermon_id' => $sermonId, 'caption_id' => $captionId],
            ['caption' => $caption, 'date' => $date, 'comment' => $comment],
        ));
    }

    public static function note(int $userId, $sermonId, ?string $body, $date): void
    {
        self::quietly(fn () => Note::updateOrCreate(
            ['user_id' => $userId, 'sermon_id' => $sermonId],
            ['body' => $body, 'date' => $date],
        ));
    }

    /** Two syncs racing to insert the same row: the other one won, which is fine. */
    private static function quietly(callable $write): void
    {
        try {
            $write();
        } catch (UniqueConstraintViolationException) {
        }
    }
}
