<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\AppController as WebAppController;
use App\Models\Cell;
use App\Models\Member;
use App\Models\Register;
use App\Support\RegisterAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Admin people directory: full members and visitors (members with
 * is_registered = false — people recorded before they're fully registered).
 */
class MemberController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $type = $request->query('type') === 'visitors' ? 'visitors' : 'members';
        $search = trim((string) $request->query('search', ''));

        $page = Member::with('cell')
            ->where('is_registered', $type === 'members')
            ->when($search !== '', fn ($q) => $this->searchScope($q, $search))
            ->orderBy($type === 'visitors' ? 'created_at' : 'first_name', $type === 'visitors' ? 'desc' : 'asc')
            ->orderBy('last_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('UI/Members/Index', [
            'people'  => collect($page->items())->map(fn (Member $m) => $this->present($m)),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total()],
            'counts'  => [
                'members'  => Member::where('is_registered', true)->count(),
                'visitors' => Member::where('is_registered', false)->count(),
            ],
            'filters' => ['type' => $type, 'search' => $search],
            'cells'   => Cell::where('verified', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Add a member or visitor; optionally mark them present on a register. */
    public function store(Request $request)
    {
        $registered = $request->boolean('isRegistered');
        $validated = $request->validate($this->rules($registered) + [
            'registerCode' => ['nullable', 'string', 'exists:registers,code'],
        ], $this->messages());

        $member = Member::create($this->attributes($validated) + [
            'code'          => (new WebAppController())->generateUniqueCode(),
            'avatar'        => 'images/avatar.png',
            'is_registered' => $registered,
        ]);

        if (! empty($validated['registerCode'])) {
            $register = Register::where('code', $validated['registerCode'])->first();
            if ($register && RegisterAttendance::isActive($register)) {
                RegisterAttendance::mark($register, $member->load('cell'));
            }
        }

        return back()->with('success', $registered ? 'Member added' : 'Visitor added');
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate($this->rules($member->is_registered), $this->messages());
        $member->update($this->attributes($validated));

        return back();
    }

    /** Turn a visitor into a full member, completing their details. */
    public function register(Request $request, Member $member)
    {
        abort_if($member->is_registered, 422, 'Already a full member.');

        $validated = $request->validate($this->rules(true), $this->messages());
        $member->update($this->attributes($validated) + ['is_registered' => true]);

        return back();
    }

    /**
     * Visitors need just a name and gender (gender is required by the
     * members table); full members also need a way to reach them.
     */
    private function rules(bool $registered): array
    {
        return [
            'firstName'                => ['required', 'string', 'max:191'],
            'middleName'               => ['nullable', 'string', 'max:191'],
            'lastName'                 => ['required', 'string', 'max:191'],
            'gender'                   => ['required', Rule::in(['Male', 'Female'])],
            'email'                    => ['nullable', 'email', 'max:191'],
            'phoneNumberAirtel'        => [$registered ? 'required_without_all:phoneNumberTnm,phoneNumberInternational,email' : 'nullable', 'nullable', 'string', 'max:20'],
            'phoneNumberTnm'           => ['nullable', 'string', 'max:20'],
            'phoneNumberInternational' => ['nullable', 'string', 'max:20'],
            'dateOfBirth'              => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'cellId'                   => ['nullable', 'integer', 'exists:cells,id'],
        ];
    }

    private function messages(): array
    {
        return [
            'phoneNumberAirtel.required_without_all' => 'Full members need at least one phone number or an email address.',
            'dateOfBirth.before'                     => 'Date of birth must be in the past.',
        ];
    }

    private function attributes(array $v): array
    {
        $phone = fn ($key) => isset($v[$key]) && $v[$key] !== '' ? str_replace(' ', '', $v[$key]) : null;

        return [
            'first_name'                 => trim($v['firstName']),
            'middle_name'                => $v['middleName'] ?? null,
            'last_name'                  => trim($v['lastName']),
            'gender'                     => $v['gender'],
            'email'                      => $v['email'] ?? null,
            'phone_number_airtel'        => $phone('phoneNumberAirtel'),
            'phone_number_tnm'           => $phone('phoneNumberTnm'),
            'phone_number_international' => $phone('phoneNumberInternational'),
            'date_of_birth'              => ! empty($v['dateOfBirth'])
                ? Carbon::createFromFormat('Y-m-d', $v['dateOfBirth'], config('app.timezone'))->startOfDay()->getTimestamp()
                : null,
            'cell_id'                    => $v['cellId'] ?? null,
        ];
    }

    private function searchScope($query, string $search)
    {
        return $query->where(fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
            ->orWhere('code', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone_number_airtel', 'like', "%{$search}%")
            ->orWhere('phone_number_tnm', 'like', "%{$search}%")
            ->orWhere('phone_number_international', 'like', "%{$search}%"));
    }

    private function present(Member $m): array
    {
        return [
            'id'           => intval($m->id),
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
            'dateOfBirth'  => $m->date_of_birth ? Carbon::createFromTimestamp($m->date_of_birth, config('app.timezone'))->format('Y-m-d') : null,
            'cellId'       => $m->cell_id ? intval($m->cell_id) : null,
            'cell'         => $m->cell?->name,
            'isRegistered' => (bool) $m->is_registered,
            'addedAt'      => $m->created_at?->getTimestamp() * 1000,
        ];
    }
}
