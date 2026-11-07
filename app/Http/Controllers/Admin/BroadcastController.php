<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cell;
use App\Models\Ministry;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** "Send notification": general communication pushed to the app (announcements scope). */
class BroadcastController extends Controller
{
    private const HISTORY_KEY = 'admin-broadcasts';

    public function create()
    {
        return Inertia::render('Admin/Broadcast', [
            'ministries' => Ministry::orderBy('id')->get(['id', 'name']),
            'cells'      => Cell::orderBy('name')->get(['code', 'name']),
            'history'    => Cache::get(self::HISTORY_KEY, []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:80'],
            'body'     => ['required', 'string', 'max:240'],
            'audience' => ['required', Rule::in(['everyone', 'ministry', 'cell'])],
            'ministry' => ['required_if:audience,ministry', 'nullable', 'integer', 'exists:ministries,id'],
            'cell'     => ['required_if:audience,cell', 'nullable', 'string', 'exists:cells,code'],
        ]);

        [$topic, $label] = match ($data['audience']) {
            'ministry' => ['SERMONS-MIN-'.$data['ministry'], Ministry::find($data['ministry'])->name.' followers'],
            'cell'     => ['CELL-'.$data['cell'], Cell::where('code', $data['cell'])->value('name').' homecell'],
            default    => ['GENERAL-V2', 'Everyone'],
        };
        Notify::topic($topic, $data['title'], $data['body'], 'general', null, legacy: $data['audience'] === 'everyone');

        $history = Cache::get(self::HISTORY_KEY, []);
        array_unshift($history, ['title' => $data['title'], 'body' => $data['body'], 'audience' => $label, 'by' => $request->user()->fullName(), 'at' => now()->getTimestamp() * 1000]);
        Cache::forever(self::HISTORY_KEY, array_slice($history, 0, 20));

        return back()->with('success', "Notification sent to {$label}");
    }
}
