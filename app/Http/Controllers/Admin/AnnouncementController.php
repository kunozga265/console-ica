<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The "announcements" page: one rich-text body with an on/off switch. */
class AnnouncementController extends Controller
{
    public function edit()
    {
        $contents = $this->contents();

        return Inertia::render('Admin/Announcements', [
            'body'    => $contents['body'] ?? '',
            'active'  => (bool) ($contents['activate'] ?? false),
            'updated' => Page::where('name', 'announcements')->value('updated_at')?->getTimestamp() * 1000,
        ]);
    }

    public function update(Request $request)
    {
        $v = $request->validate(['body' => ['nullable', 'string'], 'active' => ['required', 'boolean']]);

        Page::updateOrCreate(['name' => 'announcements'], [
            'contents' => json_encode(['body' => $v['body'] ?? '', 'activate' => $v['active'] ? 1 : 0]),
        ]);

        return back()->with('success', 'Announcements saved');
    }

    private function contents(): array
    {
        return json_decode(Page::where('name', 'announcements')->value('contents') ?? '', true) ?: [];
    }
}
