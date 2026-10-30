<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Prayer;
use Illuminate\Http\Request;

/** "I'm praying" on a prayer point (shared with the website's prayer_user). */
class PrayerController extends Controller
{
    public function praying(Request $request, Prayer $prayer)
    {
        return $this->state($prayer, (new WebAppController())->getAuthUser($request)?->id);
    }

    public function togglePraying(Request $request, Prayer $prayer)
    {
        $prayer->prayingUsers()->toggle($request->user()->id);

        return $this->state($prayer, $request->user()->id);
    }

    private function state(Prayer $prayer, ?int $userId)
    {
        return response()->json([
            'count'   => $prayer->prayingUsers()->count(),
            'praying' => $userId !== null && $prayer->prayingUsers()->whereKey($userId)->exists(),
        ]);
    }
}
