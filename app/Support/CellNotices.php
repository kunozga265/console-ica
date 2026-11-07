<?php

namespace App\Support;

use App\Models\Meeting;
use App\Models\UiNotification;
use App\Models\User;
use Carbon\Carbon;

/** Homecell activity: pushed to CELL-{code} (the app's homecell topic) and added to members' inboxes. */
class CellNotices
{
    public static function meetingScheduled(Meeting $meeting): void
    {
        $cell = $meeting->cell;
        if (! $cell) {
            return;
        }

        $when = Carbon::createFromTimestamp(intval($meeting->date), config('app.timezone'));
        $title = "{$cell->name}: next meeting";
        $body = $when->format('l j F').($when->isStartOfDay() ? '' : ' at '.$when->format('g:i A')).($meeting->venue ? " · {$meeting->venue}" : '');

        Notify::topic("CELL-{$cell->code}", $title, $body, 'cell', $cell->code, legacy: false);

        $userIds = User::whereIn('member_id', $cell->members()->pluck('id'))->pluck('id');
        foreach ($userIds as $userId) {
            UiNotification::create(['user_id' => $userId, 'title' => $title, 'body' => $body, 'type' => 'cell', 'ref' => $cell->code]);
        }
    }
}
