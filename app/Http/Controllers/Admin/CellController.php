<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Cells: create/edit/delete and verification (unverified cells can't record
 * money). Meetings, membership and the money ledger are managed on the site's
 * cell dashboard (/cells/{code}), which admins can open for any cell.
 */
class CellController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $cells = Cell::with(['zone', 'leaders', 'user'])
            ->withCount(['members', 'meetings'])
            ->withMax('meetings as last_meeting', 'date')
            ->when($status === 'unverified', fn ($q) => $q->where('verified', false))
            ->when($status === 'verified', fn ($q) => $q->where('verified', true))
            ->orderBy('verified')->orderBy('name')
            ->get();

        return Inertia::render('Admin/Cells', [
            'cells' => $cells->map(fn (Cell $c) => [
                'id'          => $c->id,
                'code'        => $c->code,
                'details'     => $c->details,
                'zoneId'      => $c->zone_id,
                'typeId'      => intval($c->type),
                'name'        => $c->name,
                'type'        => $c->getType(),
                'zone'        => $c->zone?->name,
                'location'    => $c->location,
                'leaders'     => $c->leaders->map(fn ($m) => $m->fullName())->values(),
                'owner'       => $c->user?->fullName(),
                'members'     => intval($c->members_count),
                'meetings'    => intval($c->meetings_count),
                'lastMeeting' => $c->last_meeting ? $c->last_meeting * 1000 : null,
                'balance'     => floatval($c->balance),
                'verified'    => (bool) $c->verified,
            ]),
            'status' => $status,
            'zones'  => Zone::orderBy('name')->get(['id', 'name']),
            'types'  => [['id' => 1, 'name' => 'Pastoral'], ['id' => 2, 'name' => 'Zonal'], ['id' => 3, 'name' => '114 Community'], ['id' => 4, 'name' => 'Extended']],
            'totals' => [
                'cells'      => Cell::count(),
                'unverified' => Cell::where('verified', false)->count(),
                'balance'    => floatval(Cell::where('verified', true)->sum('balance')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $v = $this->validated($request, true);
        Cell::create($this->attributes($v) + [
            'code'     => (new WebAppController())->generateUniqueCode(),
            'balance'  => $v['balance'] ?? 0,
            'user_id'  => $request->user()->id,
            'verified' => (bool) ($v['verified'] ?? true),
        ]);

        return back()->with('success', 'Cell created');
    }

    public function update(Request $request, Cell $cell)
    {
        $v = $this->validated($request, false);
        $cell->update($this->attributes($v) + ['verified' => (bool) ($v['verified'] ?? $cell->verified)]);

        return back()->with('success', 'Cell saved');
    }

    /**
     * Deletes the cell with its meetings, their attendance and its money
     * ledger. Members and leaders stay in the directory, just without a cell.
     */
    public function destroy(Cell $cell)
    {
        DB::transaction(function () use ($cell) {
            $meetingIds = $cell->meetings()->pluck('id');
            Attendance::whereIn('meeting_id', $meetingIds)->delete();
            Transaction::where('cell_id', $cell->id)->delete();
            $cell->meetings()->delete();
            Member::where('cell_id', $cell->id)->update(['cell_id' => null]);
            Member::where('leader_cell_id', $cell->id)->update(['leader_cell_id' => null]);
            $cell->delete();
        });

        return back()->with('success', 'Cell deleted');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:191'],
            'details'  => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:191'],
            'zoneId'   => ['required', 'integer', 'exists:zones,id'],
            'typeId'   => ['required', 'integer', 'in:1,2,3,4'],
            'balance'  => [$creating ? 'nullable' : 'prohibited', 'numeric', 'min:0'], // later changes go through transactions
            'verified' => ['boolean'],
        ]);
    }

    private function attributes(array $v): array
    {
        return [
            'name'     => $v['name'],
            'details'  => $v['details'] ?? null,
            'location' => $v['location'] ?? null,
            'zone_id'  => $v['zoneId'],
            'type'     => $v['typeId'],
        ];
    }

    public function verify(Cell $cell)
    {
        $cell->update(['verified' => true]);

        return back()->with('success', "{$cell->name} verified");
    }
}
