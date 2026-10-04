<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Register;
use App\Models\SermonSave;
use App\Models\UiNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Data the /ui layout shows on every page (top-bar "Live" dropdown and the
 * rail's next-cell-meeting card). Shared from HandleInertiaRequests for ui/*
 * requests, in the same shape as resources/js/Components/UI/sampleData.js.
 */
class UIShellData
{
    /** Registers dated today — services members can check in to right now. */
    public static function liveServices(Request $request): array
    {
        $memberId = $request->user()?->member_id;

        return Register::with('ministry')
            ->whereBetween('date', [Carbon::today()->getTimestamp(), Carbon::tomorrow()->getTimestamp() - 1])
            ->orderBy('date')
            ->get()
            ->map(fn (Register $register) => [
                'id'       => $register->code,
                'name'     => $register->name,
                'ministry' => $register->ministry?->name,
                'time'     => self::timeOf($register->date),
                'checked'  => $memberId !== null
                    && Attendance::where('register_id', $register->id)->where('member_id', $memberId)->exists(),
            ])
            ->all();
    }

    /**
     * The signed-in member's next meeting, across the cell they belong to and
     * the cell they lead (mirrors AppController::home). Null for guests.
     */
    public static function nextCellMeeting(Request $request): ?array
    {
        $member = $request->user()?->member;
        if ($member === null) {
            return null;
        }

        $cellIds = array_filter([$member->cell_id, $member->leader_cell_id]);
        if (empty($cellIds)) {
            return null;
        }

        $meeting = Meeting::with('cell')
            ->whereIn('cell_id', $cellIds)
            ->where('date', '>=', Carbon::now()->getTimestamp())
            ->orderBy('date')
            ->first();

        if ($meeting === null) {
            return null;
        }

        return [
            'cell' => $meeting->cell?->name,
            'date' => intval($meeting->date) * 1000,
            'time' => self::timeOf($meeting->date),
            'loc'  => $meeting->venue ?: $meeting->cell?->location,
        ];
    }

    /** IDs of the sermons on the user's saved and favorite lists. */
    public static function sermonSaves(Request $request): array
    {
        $saves = $request->user()
            ? SermonSave::where('user_id', $request->user()->id)->get(['sermon_id', 'kind'])
            : collect();

        return [
            'saved'    => $saves->where('kind', 'saved')->pluck('sermon_id')->map(fn ($id) => intval($id))->values(),
            'favorite' => $saves->where('kind', 'favorite')->pluck('sermon_id')->map(fn ($id) => intval($id))->values(),
        ];
    }

    /** The user's latest in-app notifications for the top-bar bell. */
    public static function notifications(Request $request): ?array
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        return [
            'unread' => UiNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
            'items'  => UiNotification::where('user_id', $user->id)->latest()->limit(10)->get()
                ->map(fn (UiNotification $n) => [
                    'id'    => $n->id,
                    'title' => $n->title,
                    'body'  => $n->body,
                    'url'   => $n->url,
                    'read'  => $n->read_at !== null,
                    'at'    => $n->created_at?->getTimestamp() * 1000,
                ])->all(),
        ];
    }

    /** "6:00 PM", or null when the timestamp is a bare date (midnight). */
    private static function timeOf($timestamp): ?string
    {
        $date = Carbon::createFromTimestamp($timestamp, config('app.timezone'));

        return $date->isStartOfDay() ? null : $date->format('g:i A');
    }
}
