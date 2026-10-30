<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cell;
use App\Models\Member;
use App\Models\VisitorFollowUp;
use App\Support\Visitors;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Track visitors (is_registered = false): their visits and pastoral follow-ups. Members scope. */
class VisitorController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'status', 'visitor']), 'filled');
        $status = $filters['status'] ?? 'due';
        $search = $filters['search'] ?? null;
        $tz = config('app.timezone');
        $today = now($tz)->toDateString();

        $query = Visitors::query()
            ->with('cell')
            ->when($status === 'due', fn ($q) => Visitors::needingFollowUp($q))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('phone_number_airtel', 'like', "%{$search}%")->orWhere('phone_number_tnm', 'like', "%{$search}%")
                ->orWhere('phone_number_international', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));

        $page = $query
            ->orderByRaw('next_follow_up is null and last_follow_up is not null') // planned check-ups first…
            ->orderBy('next_follow_up')                                              // …soonest first
            ->orderByDesc('last_visit')
            ->paginate(self::PER_PAGE)->withQueryString();

        $selected = isset($filters['visitor']) ? Visitors::query()->with('cell')->find($filters['visitor']) : null;

        return Inertia::render('Admin/Visitors', [
            'visitors' => collect($page->items())->map(fn (Member $m) => $this->row($m, $today)),
            'paging'   => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem()],
            'filters'  => (object) $filters,
            'counts'   => [
                'all'  => Member::where('is_registered', false)->count(),
                'due'  => Visitors::dueCount(),
                'new'  => Member::where('is_registered', false)->where('created_at', '>=', now()->subDays(30))->count(),
            ],
            // The open visitor's details (loaded with ?visitor=id).
            'visitor'  => $selected ? $this->detail($selected, $today) : null,
            'cells'    => Cell::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeFollowUp(Request $request, Member $member)
    {
        abort_if($member->is_registered, 422, 'Follow-ups are for visitors.');

        $v = $request->validate([
            'date'     => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'comment'  => ['required', 'string', 'max:5000'],
            'nextDate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
        ], [], ['nextDate' => 'next check-up']);

        $member->followUps()->create([
            'user_id'   => $request->user()->id,
            'date'      => $v['date'],
            'comment'   => $v['comment'],
            'next_date' => $v['nextDate'] ?? null,
        ]);

        return back()->with('success', 'Follow-up saved');
    }

    public function destroyFollowUp(VisitorFollowUp $followUp)
    {
        $followUp->delete();

        return back()->with('success', 'Follow-up removed');
    }

    private function row(Member $m, string $today): array
    {
        return MemberController::person($m) + [
            'visits'       => (int) $m->visits_count,
            'firstVisit'   => $m->first_visit ? intval($m->first_visit) * 1000 : null,
            'lastVisit'    => $m->last_visit ? intval($m->last_visit) * 1000 : null,
            'lastFollowUp' => $m->last_follow_up,
            'nextFollowUp' => $m->next_follow_up,
            // never contacted | check-up due/overdue | scheduled | followed up (no next date)
            'status'       => ! $m->last_follow_up ? 'new' : ($m->next_follow_up && $m->next_follow_up <= $today ? 'due' : ($m->next_follow_up ? 'scheduled' : 'done')),
        ];
    }

    private function detail(Member $m, string $today): array
    {
        $visits = DB::table('attendances')->join('registers', 'registers.id', '=', 'attendances.register_id')
            ->where('attendances.member_id', $m->id)
            ->orderByDesc('registers.date')
            ->get(['registers.code', 'registers.name', 'registers.date']);

        return $this->row($m, $today) + [
            'visitList' => $visits->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'date' => intval($r->date) * 1000])->values(),
            'followUps' => $m->followUps()->with('user:id,first_name,last_name')->get()->map(fn (VisitorFollowUp $f) => [
                'id'       => $f->id,
                'date'     => $f->date?->toDateString(),
                'comment'  => $f->comment,
                'nextDate' => $f->next_date?->toDateString(),
                'by'       => $f->user ? trim("{$f->user->first_name} {$f->user->last_name}") : null,
            ])->values(),
        ];
    }
}
