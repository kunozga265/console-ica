<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\GivingOption;

/** Giving options managed on the web (Admin → Giving Options), for the app's Give screen. */
class GivingController extends Controller
{
    public function index()
    {
        $options = GivingOption::where('active', true)->ordered()->get()->map(fn (GivingOption $o) => [
            'id'             => $o->id,
            'type'           => $o->type, // bank | mobile
            'name'           => $o->name,
            'account_name'   => $o->account_name,
            'account_number' => $o->account_number,
            'branch'         => $o->branch,
            'swift_code'     => $o->swift_code,
            'steps'          => collect(preg_split('/\r?\n/', (string) $o->instructions))->map(fn ($l) => trim($l))->filter()->values(),
        ]);

        return response()->json([
            'banks'  => $options->where('type', 'bank')->values(),
            'mobile' => $options->where('type', 'mobile')->values(),
        ]);
    }
}
