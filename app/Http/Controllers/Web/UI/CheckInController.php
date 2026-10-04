<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Register;
use App\Support\RegisterAttendance;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Member self check-in from a register's QR code. The GET only shows the
 * page; the page then POSTs to record attendance, so link previews and
 * prefetchers can't mark anyone present.
 */
class CheckInController extends Controller
{
    public function show(Request $request, Register $register)
    {
        $register->load('ministry');
        $member = $request->user()->member;

        return Inertia::render('UI/CheckIn', [
            'register' => [
                'code'     => $register->code,
                'name'     => $register->name,
                'ministry' => $register->ministry?->name,
                'date'     => intval($register->date) * 1000,
            ],
            // 'ready' | 'checked-in' | 'inactive' | 'no-member'
            'status'   => match (true) {
                $member === null                            => 'no-member',
                RegisterAttendance::isMarked($register, $member) => 'checked-in',
                ! RegisterAttendance::isActive($register)   => 'inactive',
                default                                     => 'ready',
            },
        ]);
    }

    public function store(Request $request, Register $register)
    {
        $member = $request->user()->member;
        if ($member && RegisterAttendance::isActive($register)) {
            $coords = $request->validate(['latitude' => ['nullable', 'numeric'], 'longitude' => ['nullable', 'numeric']]);
            RegisterAttendance::mark($register, $member->loadMissing('cell'), array_filter($coords) ?: null);
        }

        return back();
    }
}
