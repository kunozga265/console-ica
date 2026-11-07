<?php

namespace App\Support;

use App\Models\UiNotification;

/**
 * One place to notify people: an inbox entry (web bell + app inbox) plus a
 * push. Personal messages go to USER-{id}; broadcasts go to a topic and to the
 * legacy "general" topic for apps before 1.4.2.
 */
class Notify
{
    /**
     * @param iterable<int> $userIds
     * @param string $type sermon|prayer|event|cell|general|birthday|receipt|pop
     */
    public static function users(iterable $userIds, string $title, string $body, string $type, ?string $ref = null, ?string $url = null): void
    {
        foreach (collect($userIds)->filter()->unique() as $userId) {
            UiNotification::create(['user_id' => $userId, 'title' => $title, 'body' => $body, 'url' => $url, 'type' => $type, 'ref' => $ref]);
            Push::toUser((int) $userId, $title, $body, ['type' => $type, 'id' => $ref, 'slug' => $ref]);
        }
    }

    /** A broadcast. $legacy also sends to "general", the topic every older app listens on. */
    public static function topic(string $topic, string $title, string $body, string $type, ?string $ref = null, bool $legacy = true): void
    {
        $data = ['type' => $type, 'id' => $ref, 'slug' => $ref];
        Push::toTopic($topic, $title, $body, $data);
        if ($legacy) {
            Push::toTopic('general', $title, $body, $data);
        }
    }
}
