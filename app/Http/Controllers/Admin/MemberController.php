<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Cell;
use App\Models\CellJoinRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Directory of members and visitors. Creating/editing/registering people
 * posts to the shared ui.members.* endpoints (Web\UI\MemberController).
 */
class MemberController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['search', 'type', 'cell']), 'filled');
        $type = $filters['type'] ?? 'all';
        $search = $filters['search'] ?? null;
        $tz = config('app.timezone');

        $page = Member::with(['cell', 'leadershipCell'])
            ->when($type === 'members', fn ($q) => $q->where('is_registered', true))
            ->when($type === 'visitors', fn ($q) => $q->where('is_registered', false))
            ->when(($filters['cell'] ?? null) === 'none', fn ($q) => $q->whereNull('cell_id'))
            ->when(is_numeric($filters['cell'] ?? null), fn ($q) => $q->where('cell_id', $filters['cell']))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('code', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number_airtel', 'like', "%{$search}%")->orWhere('phone_number_tnm', 'like', "%{$search}%")))
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('Admin/Members', [
            'people' => collect($page->items())->map(fn (Member $m) => [
                'id'           => $m->id,
                'code'         => $m->code,
                'name'         => $m->fullName(),
                'firstName'    => $m->first_name,
                'middleName'   => $m->middle_name,
                'lastName'     => $m->last_name,
                'gender'       => $m->gender,
                'email'        => $m->email,
                'avatar'       => $m->avatar,
                'phoneNumberAirtel'        => $m->phone_number_airtel,
                'phoneNumberTnm'           => $m->phone_number_tnm,
                'phoneNumberInternational' => $m->phone_number_international,
                'dateOfBirth'  => $m->date_of_birth ? Carbon::createFromTimestamp($m->date_of_birth, $tz)->format('Y-m-d') : null,
                'cellId'       => $m->cell_id ? intval($m->cell_id) : null,
                'cell'         => $m->cell?->name,
                'leads'        => $m->leadershipCell?->name,
                'isRegistered' => (bool) $m->is_registered,
                'joined'       => $m->created_at?->getTimestamp() * 1000,
            ]),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total(), 'from' => $page->firstItem(), 'to' => $page->lastItem()],
            'filters' => (object) $filters,
            'stats'   => [
                'members'   => Member::where('is_registered', true)->count(),
                'new90'     => Member::where('created_at', '>=', now()->subDays(90))->count(),
                'visitors'  => Member::where('is_registered', false)->count(),
                'inCells'   => Member::whereNotNull('cell_id')->count(),
                'cells'     => Cell::where('verified', true)->count(),
            ],
            'cells' => Cell::where('verified', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Removes a person from the directory with their attendance records.
     * A linked app account is kept but unlinked (it can link again later).
     */
    public function destroy(Member $member)
    {
        DB::transaction(function () use ($member) {
            Attendance::where('member_id', $member->id)->delete();
            CellJoinRequest::where('member_id', $member->id)->delete();
            User::where('member_id', $member->id)->update(['member_id' => null]);
            $member->delete();
        });

        return back()->with('success', "{$member->fullName()} removed");
    }
}
