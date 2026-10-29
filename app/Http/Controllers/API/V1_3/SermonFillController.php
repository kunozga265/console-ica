<?php

namespace App\Http\Controllers\API\V1_3;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Sermon;
use App\Support\SermonFills;
use Illuminate\Http\Request;

/**
 * Fill-in-the-blank answers for the mobile app. GET works signed out too, so
 * the app always learns the current reveal time (admins can reveal early).
 */
class SermonFillController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $sermon = $this->sermon($slug);

        return $this->payload($sermon, (new WebAppController())->getAuthUser($request));
    }

    public function store(Request $request, string $slug)
    {
        $sermon = $this->sermon($slug);
        $v = $request->validate(SermonFills::rules($sermon));
        SermonFills::save($request->user(), $sermon, $v['key'], $v['value'] ?? null);

        return $this->payload($sermon, $request->user());
    }

    private function sermon(string $slug): Sermon
    {
        $sermon = Sermon::where('slug', $slug)->latest('id')->first();
        abort_unless($sermon, 404, 'Sermon not found');

        return $sermon;
    }

    private function payload(Sermon $sermon, $user)
    {
        return response()->json([
            'reveal_at' => $sermon->fillsRevealAt(),
            'fills' => (object) SermonFills::forUser($user, $sermon),
        ]);
    }
}
