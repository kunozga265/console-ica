<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** The documents members can view/download on the site (`downloads` table). */
class ResourceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Resources', [
            'resources' => Download::orderByDesc('date')->orderByDesc('id')->get()->map(fn (Download $d) => [
                'id'          => $d->id,
                'title'       => $d->title,
                'type'        => $d->type,
                'path'        => $d->path,
                'description' => $d->description,
                'date'        => $d->date ? Carbon::createFromTimestamp($d->date, config('app.timezone'))->format('Y-m-d') : null,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request);
        Download::create($this->attributes($v) + ['slug' => Str::slug($v['title']) . date('-Y-m-d')]);

        return back()->with('success', 'Resource added');
    }

    public function update(Request $request, Download $download)
    {
        $download->update($this->attributes($this->validated($request)));

        return back()->with('success', 'Resource saved');
    }

    public function destroy(Download $download)
    {
        $download->delete();

        return back()->with('success', 'Resource deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:191'],
            'type'        => ['required', 'string', 'in:pdf,ppt,doc,xls,link'],
            'path'        => ['required', 'url', 'max:191'],
            'description' => ['nullable', 'string'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);
    }

    private function attributes(array $v): array
    {
        return [
            'title'       => $v['title'],
            'type'        => $v['type'],
            'path'        => $v['path'],
            'description' => $v['description'] ?? null,
            'date'        => Carbon::createFromFormat('Y-m-d', $v['date'], config('app.timezone'))->startOfDay()->getTimestamp(),
        ];
    }
}
