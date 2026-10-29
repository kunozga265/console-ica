<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Sermon;
use App\Support\SermonFills;
use Illuminate\Http\Request;

class SermonFillController extends Controller
{
    public function store(Request $request, Sermon $sermon)
    {
        $v = $request->validate(SermonFills::rules($sermon));
        SermonFills::save($request->user(), $sermon, $v['key'], $v['value'] ?? null);

        return back();
    }

    /** Behind auth: guests are sent to sign in, then land back on the sermon. */
    public function signin(Sermon $sermon)
    {
        return redirect()->route('ui.sermons.show', $sermon->slug);
    }
}
