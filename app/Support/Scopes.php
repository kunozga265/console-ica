<?php

namespace App\Support;

/**
 * What a user may manage — one scope per admin section. Super users have all
 * of them and are the only ones who can assign scopes (Admin → Users).
 */
class Scopes
{
    public const ALL = [
        'sermons'       => ['label' => 'Sermons', 'hint' => 'Sermons, series, ministers and resources'],
        'prayer'        => ['label' => 'Prayer points', 'hint' => 'Daily prayer points'],
        'events'        => ['label' => 'Events', 'hint' => 'Events and RSVPs'],
        'announcements' => ['label' => 'Announcements', 'hint' => 'Announcement banner'],
        'giving'        => ['label' => 'Giving', 'hint' => 'Giving options'],
        'ministries'    => ['label' => 'Ministries', 'hint' => 'Ministries and their leaders'],
        'cells'         => ['label' => 'Cells', 'hint' => 'Cells, zones and cell verification'],
        'registers'     => ['label' => 'Registers', 'hint' => 'Service registers, attendance and adding visitors'],
        'members'       => ['label' => 'Members', 'hint' => 'Member directory, visitors and follow-ups'],
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /** Options for the admin scope editor. */
    public static function options(): array
    {
        return array_map(fn ($key) => ['key' => $key] + self::ALL[$key], self::keys());
    }
}
