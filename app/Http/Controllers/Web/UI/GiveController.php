<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\GivingOption;
use Inertia\Inertia;

/** The Give page: active giving options managed in /admin/giving. */
class GiveController extends Controller
{
    public function __invoke()
    {
        $options = GivingOption::where('active', true)->ordered()->get()->map(fn (GivingOption $o) => [
            'id'            => $o->id,
            'type'          => $o->type,
            'name'          => $o->name,
            'accountName'   => $o->account_name,
            'accountNumber' => $o->account_number,
            'branch'        => $o->branch,
            'swiftCode'     => $o->swift_code,
            'steps'         => collect(preg_split('/\r?\n/', (string) $o->instructions))->map(fn ($l) => trim($l))->filter()->values(),
        ]);

        return Inertia::render('UI/Give', [
            'banks'  => $options->where('type', 'bank')->values(),
            'mobile' => $options->where('type', 'mobile')->values(),
        ]);
    }
}
