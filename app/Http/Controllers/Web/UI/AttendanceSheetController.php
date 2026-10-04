<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Member;
use App\Models\Ministry;
use App\Models\Register;
use App\Support\RegisterAttendance;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Admin-only service registers ("attendance sheets"); see routes/web.php. */
class AttendanceSheetController extends Controller
{
    private const PER_PAGE = 20;

    /** Page components and route names; the admin console's RegisterController overrides these. */
    protected string $indexPage = 'UI/AttendanceSheets/Index';
    protected string $showPage = 'UI/AttendanceSheets/Show';
    protected string $indexRoute = 'ui.attendance';
    protected string $showRoute = 'ui.attendance.show';

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $today = [Carbon::today()->getTimestamp(), Carbon::tomorrow()->getTimestamp() - 1];

        $base = fn () => Register::with('ministry')
            ->withCount(['members as attendee_count'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"));

        $other = $base()->whereNotBetween('date', $today)->orderByDesc('date')->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render($this->indexPage, [
            'active'  => $base()->whereBetween('date', $today)->orderBy('date')->get()->map(fn ($r) => $this->present($r)),
            'others'  => [
                'data'     => collect($other->items())->map(fn ($r) => $this->present($r)),
                'page'     => $other->currentPage(),
                'lastPage' => $other->lastPage(),
                'total'    => $other->total(),
            ],
            'filters'    => ['search' => $search],
            'ministries' => $this->ministries(),
        ]);
    }

    public function show(Request $request, Register $register)
    {
        $register->load('ministry');
        $query = trim((string) $request->query('q', ''));

        $attendees = Attendance::with('member.cell')
            ->where('register_id', $register->id)
            ->orderByDesc('date')
            ->get()
            ->filter(fn ($a) => $a->member)
            ->unique('member_id')
            ->map(fn (Attendance $a) => $this->member($a->member) + ['markedAt' => $a->date ? intval($a->date) * 1000 : null])
            ->values();

        $markedIds = $attendees->pluck('id')->all();

        // Member search for marking (by name, code or phone).
        $results = $query === '' ? [] : Member::with('cell')
            ->where(fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                ->orWhere('code', 'like', "%{$query}%")
                ->orWhere('phone_number_airtel', 'like', "%{$query}%")
                ->orWhere('phone_number_tnm', 'like', "%{$query}%"))
            ->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(fn (Member $m) => $this->member($m) + ['marked' => in_array($m->id, $markedIds)])
            ->values();

        $checkInUrl = route('ui.check-in', $register->code);

        return Inertia::render($this->showPage, [
            'register'   => $this->present($register, $attendees->count()),
            'attendees'  => $attendees,
            'results'    => $results,
            'q'          => $query,
            'checkInUrl' => $checkInUrl,
            'ministries' => $this->ministries(),
            'cells'      => \App\Models\Cell::where('verified', true)->orderBy('name')->get(['id', 'name']),
            'qrSvg'      => (new Writer(new ImageRenderer(new RendererStyle(260, 1), new SvgImageBackEnd())))->writeString($checkInUrl),
        ]);
    }

    public function toggle(Request $request, Register $register)
    {
        $validated = $request->validate([
            'memberId' => ['required', 'integer', 'exists:members,id'],
            'marked'   => ['required', 'boolean'],
        ]);

        if (! RegisterAttendance::isActive($register)) {
            return back()->withErrors(['register' => 'Only today\'s registers can be marked.']);
        }

        $member = Member::with('cell')->findOrFail($validated['memberId']);
        $validated['marked'] ? RegisterAttendance::mark($register, $member) : RegisterAttendance::unmark($register, $member);

        return back();
    }

    public function store(Request $request)
    {
        $validated = $this->validateRegister($request);

        $register = Register::create([
            'code'        => (new WebAppController())->generateUniqueCode(),
            'name'        => $validated['name'],
            'ministry_id' => $validated['ministryId'],
            'date'        => $this->timestamp($validated['date']),
        ]);

        return redirect()->route($this->showRoute, $register->code);
    }

    public function update(Request $request, Register $register)
    {
        $validated = $this->validateRegister($request);

        $register->update([
            'name'        => $validated['name'],
            'ministry_id' => $validated['ministryId'],
            'date'        => $this->timestamp($validated['date']),
        ]);

        return back();
    }

    /** Deletes the register and its attendance records (as the old console did). */
    public function destroy(Register $register)
    {
        Attendance::where('register_id', $register->id)->delete();
        $register->delete();

        return redirect()->route($this->indexRoute);
    }

    private function validateRegister(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:191'],
            'ministryId' => ['required', 'integer', 'exists:ministries,id'],
            'date'       => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
    }

    private function timestamp(string $local): int
    {
        return Carbon::createFromFormat('Y-m-d\TH:i', $local, config('app.timezone'))->getTimestamp();
    }

    private function ministries()
    {
        return Ministry::orderByRaw("slug = 'main-church' DESC")->orderBy('name')->get(['id', 'name']);
    }

    private function present(Register $register, ?int $count = null): array
    {
        return [
            'code'          => $register->code,
            'name'          => $register->name,
            'ministry'      => $register->ministry?->name,
            'ministryId'    => $register->ministry_id ? intval($register->ministry_id) : null,
            'date'          => intval($register->date) * 1000,
            'active'        => RegisterAttendance::isActive($register),
            'attendeeCount' => $count ?? intval($register->attendee_count),
        ];
    }

    private function member(Member $m): array
    {
        return [
            'id'     => intval($m->id),
            'name'   => $m->fullName(),
            'avatar' => $m->avatar,
            'cell'   => $m->cell?->name,
            'code'   => $m->code,
            'isRegistered' => (bool) ($m->is_registered ?? true),
        ];
    }
}
