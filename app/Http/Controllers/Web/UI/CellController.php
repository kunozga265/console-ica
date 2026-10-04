<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\CellJoinRequest;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\UiNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * The signed-in member's cell dashboard. A member can lead one cell
 * (members.leader_cell_id) and belong to another (members.cell_id); the
 * leadership cell is shown first. Leaders, the cell's owner (cells.user_id)
 * and admins manage it; other members get a read-only view.
 *
 * Meeting/offering/transaction bookkeeping follows API\V2\MeetingController
 * and API\V2\TransactionController.
 */
class CellController extends Controller
{
    /** The member's default cell (leadership first), or the "find a cell" page. */
    public function index(Request $request)
    {
        $member = $request->user()->member;
        $cell = $member?->leadershipCell ?? $member?->cell;

        return $cell ? $this->dashboard($request, $cell) : $this->find($request);
    }

    public function show(Request $request, Cell $cell)
    {
        abort_unless($this->canView($request->user(), $cell), 403);

        return $this->dashboard($request, $cell);
    }

    /* ------------------------------------------------------------------ */
    /*  Find a cell / join requests                                        */
    /* ------------------------------------------------------------------ */

    private function find(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        $cells = Cell::with(['zone', 'leaders'])
            ->withCount('members')
            ->where('verified', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
                ->orWhereHas('zone', fn ($z) => $z->where('name', 'like', "%{$q}%"))))
            ->orderBy('name')
            ->limit(40)
            ->get();

        $pending = CellJoinRequest::where('user_id', $user->id)->where('status', 'pending')->pluck('cell_id')->all();

        return Inertia::render('UI/Cells/Find', [
            'cells' => $cells->map(fn (Cell $c) => [
                'code'        => $c->code,
                'name'        => $c->name,
                'location'    => $c->location,
                'zone'        => $c->zone?->name,
                'type'        => $c->getType(),
                'leaders'     => $c->leaders->map(fn ($m) => $m->fullName())->values(),
                'memberCount' => intval($c->members_count),
                'requested'   => in_array($c->id, $pending),
            ])->values(),
            'q'         => $q,
            'hasMember' => $user->member_id !== null,
        ]);
    }

    public function requestToJoin(Request $request, Cell $cell)
    {
        $user = $request->user();
        $member = $user->member;
        if (! $member) {
            return back()->withErrors(['cell' => 'Your account needs a linked member profile before you can join a cell.']);
        }

        $message = $request->validate(['message' => ['nullable', 'string', 'max:500']])['message'] ?? null;

        $joinRequest = CellJoinRequest::firstOrCreate(
            ['cell_id' => $cell->id, 'user_id' => $user->id, 'status' => 'pending'],
            ['member_id' => $member->id, 'message' => $message],
        );

        if ($joinRequest->wasRecentlyCreated) {
            UiNotification::send(
                $this->managerUserIds($cell),
                "{$member->fullName()} wants to join {$cell->name}",
                $message,
                route('ui.cells.show', $cell->code) . '#requests',
            );
        }

        return back();
    }

    public function decideJoinRequest(Request $request, Cell $cell, CellJoinRequest $joinRequest)
    {
        $this->authorizeManage($request, $cell);
        abort_unless($joinRequest->cell_id === $cell->id && $joinRequest->status === 'pending', 404);

        $approve = $request->validate(['approve' => ['required', 'boolean']])['approve'];

        DB::transaction(function () use ($approve, $joinRequest, $cell, $request) {
            $joinRequest->update(['status' => $approve ? 'approved' : 'declined', 'handled_by' => $request->user()->id]);
            if ($approve) {
                $joinRequest->member?->update(['cell_id' => $cell->id]);
            }
        });

        UiNotification::send(
            [$joinRequest->user_id],
            $approve ? "You've been added to {$cell->name}" : "Your request to join {$cell->name} was declined",
            $approve ? 'Welcome to the cell! You can now see its meetings and members.' : 'You can ask another cell, or speak to the church office.',
            route('ui.cells'),
        );

        return back();
    }

    /* ------------------------------------------------------------------ */
    /*  Dashboard                                                          */
    /* ------------------------------------------------------------------ */

    private function dashboard(Request $request, Cell $cell)
    {
        $user = $request->user();
        $member = $user->member;
        $canManage = $this->canManage($user, $cell);

        $cell->load(['zone', 'leaders', 'members' => fn ($q) => $q->orderBy('first_name')->orderBy('last_name')]);
        $meetings = $cell->meetings()->withCount('attendances')->with('attendances:id,meeting_id,member_id')->orderByDesc('date')->get();

        // The member's two possible cells, for the leadership/member toggle.
        $mine = collect([
            'leadership' => $member?->leadershipCell,
            'member'     => $member?->cell,
        ])->filter()->map(fn (Cell $c) => ['code' => $c->code, 'name' => $c->name]);

        $memberQuery = trim((string) $request->query('memberQ', ''));

        return Inertia::render('UI/Cells/Show', [
            'cell' => [
                'code'        => $cell->code,
                'name'        => $cell->name,
                'details'     => $cell->details,
                'location'    => $cell->location,
                'zone'        => $cell->zone?->name,
                'type'        => $cell->getType(),
                'verified'    => (bool) $cell->verified,
                'balance'     => $canManage ? floatval($cell->balance) : null,
                'leaders'     => $cell->leaders->map(fn ($m) => $m->fullName())->values(),
                'memberCount' => $cell->members->count(),
            ],
            'mine'      => $mine->all(),
            'role'      => $mine->search(fn ($c) => $c['code'] === $cell->code) ?: null, // 'leadership' | 'member' | null
            'canManage' => $canManage,
            'chart'     => $meetings->sortBy('date')->values()->map(fn (Meeting $m) => [
                'date'      => intval($m->date) * 1000,
                'attendees' => intval($m->attendances_count),
            ]),
            'meetings'  => $meetings->map(fn (Meeting $m) => [
                'code'      => $m->code,
                'date'      => intval($m->date) * 1000,
                'venue'     => $m->venue,
                'offering'  => $canManage ? floatval($m->offering) : null,
                'attendees' => intval($m->attendances_count),
                'memberIds' => $m->attendances->pluck('member_id')->map(fn ($id) => intval($id))->unique()->values(),
            ]),
            'members' => $cell->members->map(fn (Member $m) => $this->member($m) + [
                'isLeader' => intval($m->leader_cell_id) === $cell->id,
            ])->values(),
            'transactions' => $canManage
                ? $cell->transactions()->latest()->limit(100)->get()->map(fn (Transaction $t) => [
                    'id'          => $t->id,
                    'date'        => $t->created_at?->getTimestamp() * 1000,
                    'description' => $t->description,
                    'amount'      => floatval($t->amount),
                    'type'        => intval($t->type), // 0 = in, 1 = out
                    'balance'     => floatval($t->balance),
                ])
                : [],
            'joinRequests' => $canManage
                ? CellJoinRequest::with(['user', 'member'])->where('cell_id', $cell->id)->where('status', 'pending')->latest()->get()
                    ->map(fn (CellJoinRequest $r) => [
                        'id'      => $r->id,
                        'name'    => $r->member?->fullName() ?? $r->user?->fullName(),
                        'avatar'  => $r->member?->avatar,
                        'message' => $r->message,
                        'date'    => $r->created_at?->getTimestamp() * 1000,
                    ])
                : [],
            'otherCells' => $canManage
                ? Cell::where('verified', true)->whereKeyNot($cell->id)->orderBy('name')->get(['id', 'name'])
                : [],
            'memberQ'       => $memberQuery,
            'memberResults' => $canManage && $memberQuery !== ''
                ? Member::with('cell')
                    ->where(fn ($q) => $q->whereNull('cell_id')->orWhere('cell_id', '!=', $cell->id))
                    ->where(fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$memberQuery}%"])
                        ->orWhere('code', 'like', "%{$memberQuery}%")
                        ->orWhere('phone_number_airtel', 'like', "%{$memberQuery}%")
                        ->orWhere('phone_number_tnm', 'like', "%{$memberQuery}%"))
                    ->orderBy('first_name')->limit(12)->get()
                    ->map(fn (Member $m) => $this->member($m) + ['currentCell' => $m->cell?->name])
                : [],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Members                                                            */
    /* ------------------------------------------------------------------ */

    public function attachMember(Request $request, Cell $cell)
    {
        $this->authorizeManage($request, $cell);
        $memberId = $request->validate(['memberId' => ['required', 'integer', 'exists:members,id']])['memberId'];

        Member::whereKey($memberId)->update(['cell_id' => $cell->id]);

        return back();
    }

    public function detachMember(Request $request, Cell $cell, Member $member)
    {
        $this->authorizeManage($request, $cell);
        abort_unless(intval($member->cell_id) === $cell->id, 404);

        $member->update(['cell_id' => null]);

        return back();
    }

    public function transferMember(Request $request, Cell $cell, Member $member)
    {
        $this->authorizeManage($request, $cell);
        abort_unless(intval($member->cell_id) === $cell->id, 404);
        $targetId = $request->validate(['cellId' => ['required', 'integer', 'exists:cells,id']])['cellId'];

        $member->update(['cell_id' => $targetId]);

        return back();
    }

    /* ------------------------------------------------------------------ */
    /*  Meetings, offering, attendance                                     */
    /* ------------------------------------------------------------------ */

    public function storeMeeting(Request $request, Cell $cell)
    {
        $this->authorizeManage($request, $cell);
        $validated = $request->validate([
            'date'  => ['required', 'date_format:Y-m-d\TH:i'],
            'venue' => ['required', 'string', 'max:191'],
        ]);

        $cell->meetings()->create([
            'code'     => (new WebAppController())->generateUniqueCode(),
            'date'     => $this->timestamp($validated['date']),
            'venue'    => $validated['venue'],
            'offering' => 0,
        ]);

        return back();
    }

    /** Edit a meeting: date/venue, attendance and offering (with its transaction). */
    public function updateMeeting(Request $request, Cell $cell, Meeting $meeting)
    {
        $this->authorizeManage($request, $cell);
        abort_unless(intval($meeting->cell_id) === $cell->id, 404);

        $validated = $request->validate([
            'date'      => ['required', 'date_format:Y-m-d\TH:i'],
            'venue'     => ['required', 'string', 'max:191'],
            'offering'  => ['required', 'numeric', 'min:0'],
            'memberIds' => ['array'],
            'memberIds.*' => ['integer'],
        ]);

        $offeringChanged = floatval($validated['offering']) !== floatval($meeting->offering);
        if ($offeringChanged && ! $cell->verified) {
            return back()->withErrors(['meeting' => 'This cell is not verified yet, so offerings can\'t be recorded. Please contact the church office.']);
        }

        $transaction = $meeting->transactions()->first();
        if ($offeringChanged && $transaction && $cell->transactions()->where('created_at', '>', $transaction->created_at)->exists()) {
            return back()->withErrors(['meeting' => 'Offering not updated — a later transaction already exists, so the account statement would be violated. Please add the difference to the next meeting.']);
        }

        DB::transaction(function () use ($validated, $meeting, $cell, $transaction, $offeringChanged) {
            $meeting->update(['date' => $this->timestamp($validated['date']), 'venue' => $validated['venue']]);

            // Attendance: replace with the submitted list (only this cell's members).
            $allowed = $cell->members()->pluck('id')->all();
            $meeting->attendances()->delete();
            foreach (array_intersect(array_unique($validated['memberIds'] ?? []), $allowed) as $memberId) {
                Attendance::create(['member_id' => $memberId, 'meeting_id' => $meeting->id, 'zone_id' => $cell->zone_id]);
            }

            if (! $offeringChanged) {
                return;
            }

            $offering = floatval($validated['offering']);
            $cell->refresh();
            $newBalance = $transaction
                ? $cell->balance - $transaction->amount + $offering
                : $cell->balance + $offering;

            $meeting->update(['offering' => $offering]);
            $cell->update(['balance' => $newBalance]);

            $transaction
                ? $transaction->update(['amount' => $offering, 'description' => 'Cell offering', 'balance' => $newBalance])
                : Transaction::create([
                    'amount' => $offering, 'type' => 0, 'description' => 'Cell offering',
                    'meeting_id' => $meeting->id, 'cell_id' => $cell->id, 'balance' => $newBalance,
                ]);
        });

        return back();
    }

    /* ------------------------------------------------------------------ */
    /*  Transactions                                                       */
    /* ------------------------------------------------------------------ */

    public function storeTransaction(Request $request, Cell $cell)
    {
        $this->authorizeManage($request, $cell);
        $validated = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'type'        => ['required', 'integer', 'in:0,1'],
            'description' => ['required', 'string', 'max:191'],
        ]);

        if (! $cell->verified) {
            return back()->withErrors(['transaction' => 'This cell is not verified yet. Please contact the church office.']);
        }

        DB::transaction(function () use ($validated, $cell) {
            $cell->refresh();
            $newBalance = $validated['type'] == 0 ? $cell->balance + $validated['amount'] : $cell->balance - $validated['amount'];
            $cell->update(['balance' => $newBalance]);
            Transaction::create([
                'amount' => $validated['amount'], 'type' => $validated['type'], 'description' => $validated['description'],
                'cell_id' => $cell->id, 'balance' => $newBalance,
            ]);
        });

        return back();
    }

    /* ------------------------------------------------------------------ */

    private function canManage(User $user, Cell $cell): bool
    {
        return intval($user->member?->leader_cell_id) === $cell->id
            || intval($cell->user_id) === $user->id
            || $user->hasAnyRole(['admin', 'super']);
    }

    private function canView(User $user, Cell $cell): bool
    {
        return $this->canManage($user, $cell) || intval($user->member?->cell_id) === $cell->id;
    }

    private function authorizeManage(Request $request, Cell $cell): void
    {
        abort_unless($this->canManage($request->user(), $cell), 403);
    }

    /** Users who manage a cell: its owner and the users linked to its leaders. */
    private function managerUserIds(Cell $cell): array
    {
        $leaderUsers = User::whereIn('member_id', $cell->leaders()->pluck('id'))->pluck('id');

        return $leaderUsers->push($cell->user_id)->filter()->unique()->values()->all();
    }

    private function timestamp(string $local): int
    {
        return Carbon::createFromFormat('Y-m-d\TH:i', $local, config('app.timezone'))->getTimestamp();
    }

    private function member(Member $m): array
    {
        return [
            'id'     => intval($m->id),
            'name'   => $m->fullName(),
            'avatar' => $m->avatar,
            'code'   => $m->code,
            'phone'  => $m->phone_number_airtel ?: ($m->phone_number_tnm ?: $m->phone_number_international),
        ];
    }
}
