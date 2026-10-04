<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GivingOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Bank accounts and mobile-money numbers shown on the site's Give page. */
class GivingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Giving', ['options' => GivingOption::ordered()->get()]);
    }

    public function store(Request $request)
    {
        GivingOption::create($this->validated($request));

        return back()->with('success', 'Giving option added');
    }

    public function update(Request $request, GivingOption $option)
    {
        $option->update($this->validated($request));

        return back()->with('success', 'Giving option saved');
    }

    public function destroy(GivingOption $option)
    {
        $option->delete();

        return back()->with('success', 'Giving option deleted');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'type'           => ['required', Rule::in(['bank', 'mobile'])],
            'name'           => ['required', 'string', 'max:191'],
            'account_name'   => ['nullable', 'string', 'max:191'],
            'account_number' => ['required', 'string', 'max:191'],
            'branch'         => ['nullable', 'string', 'max:191'],
            'swift_code'     => ['nullable', 'string', 'max:191'],
            'instructions'   => ['nullable', 'string'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'active'         => ['boolean'],
        ]);

        if ($v['type'] === 'mobile') {
            $v['branch'] = $v['swift_code'] = null;
        }

        return $v + ['sort_order' => 0, 'active' => true];
    }
}
