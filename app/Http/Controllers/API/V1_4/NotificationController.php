<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\UiNotification;
use App\Support\NotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Notification preferences (and the topics the app should subscribe to) plus the personal inbox. */
class NotificationController extends Controller
{
    public function settings(Request $request)
    {
        return response()->json(NotificationSettings::forUser($request->user()));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'categories'   => ['sometimes', 'array'],
            'categories.*' => ['boolean'],
            'ministries'   => ['sometimes', 'array'],
            'ministries.*' => ['integer', Rule::exists('ministries', 'id')],
        ]);
        NotificationSettings::update($request->user(), $data);

        return response()->json(NotificationSettings::forUser($request->user()));
    }

    public function index(Request $request)
    {
        $items = UiNotification::where('user_id', $request->user()->id)->latest()->limit(50)->get();

        return response()->json([
            'unread' => $items->whereNull('read_at')->count(),
            'data'   => $items->map(fn (UiNotification $n) => [
                'id'    => $n->id,
                'title' => $n->title,
                'body'  => $n->body,
                'type'  => $n->type,
                'ref'   => $n->ref,
                'read'  => $n->read_at !== null,
                'date'  => $n->created_at?->getTimestamp(),
            ])->values(),
        ]);
    }

    public function read(Request $request)
    {
        UiNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['unread' => 0]);
    }
}
