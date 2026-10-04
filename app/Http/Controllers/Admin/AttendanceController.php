<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\Member;
use App\Models\Register;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Attendance analytics. Taking attendance itself happens on the site's
 * attendance sheets (front-of-house, QR check-in), linked from here.
 */
class AttendanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $tz = config('app.timezone');
        $registers = Register::with('ministry')->withCount('members')
            ->where('date', '<=', Carbon::tomorrow($tz)->getTimestamp())
            ->orderByDesc('date')->limit(30)->get();

        // The register being looked at (defaults to the latest).
        $selected = $registers->firstWhere('code', $request->query('register')) ?? $registers->first();
        $previous = $selected ? $registers->first(fn ($r) => $r->date < $selected->date && $r->ministry_id === $selected->ministry_id) : null;

        $present = $selected
            ? Member::whereIn('id', Attendance::where('register_id', $selected->id)->pluck('member_id'))->get(['id', 'gender', 'is_registered', 'created_at'])
            : collect();

        return Inertia::render('Admin/Attendance', [
            'registers' => $registers->map(fn (Register $r) => [
                'code' => $r->code, 'name' => $r->name, 'ministry' => $r->ministry?->name,
                'date' => $r->date * 1000, 'present' => intval($r->members_count),
            ]),
            'selected'  => $selected?->code,
            'summary'   => $selected ? [
                'name'      => $selected->name,
                'date'      => $selected->date * 1000,
                'present'   => $present->count(),
                'previous'  => $previous ? intval($previous->members_count) : null,
                'visitors'  => $present->where('is_registered', false)->count(),
                // People added to the directory in the week of this service.
                'firstTime' => $present->filter(fn ($m) => $m->created_at && $m->created_at->getTimestamp() >= $selected->date - 7 * 86400)->count(),
                'gender'    => ['Male' => $present->where('gender', 'Male')->count(), 'Female' => $present->where('gender', 'Female')->count()],
                'membersTotal' => Member::where('is_registered', true)->count(),
            ] : null,
            'trend' => $registers->take(8)->reverse()->values()->map(fn (Register $r) => [
                'label' => Carbon::createFromTimestamp($r->date, $tz)->format('j M'),
                'name'  => $r->name,
                'value' => intval($r->members_count),
            ]),
            'cells' => $this->byCell(),
        ]);
    }

    /** For each cell: members, and how many were present at its latest meeting. */
    private function byCell(): array
    {
        return Cell::with(['leaders', 'meetings' => fn ($q) => $q->withCount('attendances')->orderByDesc('date')])
            ->withCount('members')->where('verified', true)->orderBy('name')->get()
            ->map(function (Cell $c) {
                $last = $c->meetings->first();

                return [
                    'name'     => $c->name,
                    'leaders'  => $c->leaders->map(fn ($m) => $m->fullName())->join(', '),
                    'expected' => intval($c->members_count),
                    'present'  => $last ? intval($last->attendances_count) : null,
                    'lastMeeting' => $last ? $last->date * 1000 : null,
                    'meetings' => $c->meetings->count(),
                ];
            })->all();
    }
}
