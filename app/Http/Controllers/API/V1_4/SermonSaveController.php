<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\Sermon;
use App\Models\SermonSave;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Starred (favorite) and saved-for-later sermons — the same lists as the website. */
class SermonSaveController extends Controller
{
    public function index(Request $request)
    {
        return $this->lists($request->user()->id);
    }

    public function toggle(Request $request, Sermon $sermon)
    {
        $kind = $request->validate(['kind' => ['required', Rule::in(SermonSave::KINDS)]])['kind'];
        $attrs = ['sermon_id' => $sermon->id, 'user_id' => $request->user()->id, 'kind' => $kind];

        $existing = SermonSave::where($attrs)->first();
        $existing ? $existing->delete() : SermonSave::create($attrs);

        return $this->lists($request->user()->id);
    }

    private function lists(int $userId)
    {
        $saves = SermonSave::where('user_id', $userId)->latest()->get(['sermon_id', 'kind']);

        return response()->json([
            'saved'    => $saves->where('kind', 'saved')->pluck('sermon_id')->map(fn ($id) => intval($id))->values(),
            'favorite' => $saves->where('kind', 'favorite')->pluck('sermon_id')->map(fn ($id) => intval($id))->values(),
        ]);
    }
}
