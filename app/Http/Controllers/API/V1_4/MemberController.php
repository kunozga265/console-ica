<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\API\V1_3\MemberController as V1_3MemberController;
use App\Http\Controllers\Web\AppController;
use App\Http\Resources\MemberResource;
use App\Models\Cell;
use App\Models\Member;
use App\Models\Register;
use App\Support\Age;
use App\Support\MemberAccess;
use App\Support\RegisterAttendance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API 1.4 members: same payloads as 1.3, now authorised by scope
 * (App\Support\MemberAccess), plus visitors added at a register and the
 * signed-in user's own profile (/members/me).
 */
class MemberController extends V1_3MemberController
{
    public function index()
    {
        abort_unless(MemberAccess::canList(request()->user()), 403, "You don't have access to the member directory.");

        return parent::index();
    }

    /**
     * Add a person. New in 1.4: is_registered=false adds a visitor, and
     * register_code marks them present at that service in the same call.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'gender'        => ['required', Rule::in(['Male', 'Female'])],
            'is_registered' => ['sometimes', 'boolean'],
            'register_code' => ['nullable', 'string', 'exists:registers,code'],
            'cell_code'     => ['nullable', 'string'],
        ]);

        $cell = $request->filled('cell_code') ? Cell::where('code', $request->cell_code)->first() : null;
        abort_unless(MemberAccess::canCreate($request->user(), $cell), 403, "You don't have access to add people.");

        if ($request->type === 'CELL' && ! $cell?->verified) {
            return response()->json(['message' => 'Cell not verified. Please contact system administrator.'], 400);
        }

        $visitor = $request->has('is_registered') && ! $request->boolean('is_registered');
        $minor = Age::isMinor($request->date_of_birth);
        $phones = array_filter($request->only(['phone_number_airtel', 'phone_number_tnm', 'phone_number_international']));
        // Visitors may be added with just a name, and children/teenagers (18 or under) may use a
        // guardian contact instead; everyone else needs a way to reach them.
        if (! $visitor && ! $minor && ! $phones) {
            return response()->json(['message' => 'Please enter at least one phone number'], 400);
        }
        foreach ($phones as $column => $number) {
            if ($existing = Member::where($column, $number)->first()) {
                return response()->json([
                    'member'  => new MemberResource($existing),
                    'message' => 'A member with this phone number already exists',
                ], 406);
            }
        }

        $member = Member::create([
            'code'          => (new AppController())->generateUniqueCode(),
            'avatar'        => 'images/avatar.png',
            'first_name'    => ucwords($request->first_name),
            'middle_name'   => ucwords((string) $request->middle_name),
            'other_name'    => ucwords((string) $request->other_name),
            'last_name'     => ucwords($request->last_name),
            'gender'        => $request->gender,
            'cell_id'       => $cell?->id,
            'phone_number_airtel'        => $request->phone_number_airtel,
            'phone_number_tnm'           => $request->phone_number_tnm,
            'phone_number_international' => $request->phone_number_international,
            'email'         => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'is_registered' => ! $visitor,
            'guardian_name'  => $request->guardian_name ?: null,
            'guardian_phone' => $request->guardian_phone ?: null,
        ]);

        if ($request->filled('register_code')) {
            RegisterAttendance::mark(Register::where('code', $request->register_code)->first(), $member->loadMissing('cell'));
        }

        return response()->json([
            'message' => $visitor ? 'Visitor added!' : 'Member added!',
            'member'  => new MemberResource($member->fresh('cell')),
        ]);
    }

    public function update(Request $request, $code)
    {
        $member = Member::where('code', $code)->first();
        if (! $member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
        abort_unless(MemberAccess::canUpdate($request->user(), $member), 403, "You can't edit this person.");

        $response = parent::update($request, $code);
        if ($response->getStatusCode() === 200 && $request->hasAny(['guardian_name', 'guardian_phone'])) {
            $member->update([
                'guardian_name'  => $request->guardian_name ?: null,
                'guardian_phone' => $request->guardian_phone ?: null,
            ]);
        }

        return $response;
    }

    public function batchAdd(Request $request)
    {
        $user = $request->user();
        $cellIds = collect($request->input('members', []))->pluck('cell_id')->filter()->unique();
        $allowed = $user->hasAnyScope('members', 'cells')
            || ($cellIds->count() === 1 && MemberAccess::leadsCell($user, Cell::find($cellIds->first())));
        abort_unless($allowed, 403, "You don't have access to add these people.");

        return parent::batchAdd($request);
    }

    /** The signed-in user's own member profile. */
    public function me(Request $request)
    {
        $member = $request->user()->member;
        if (! $member) {
            return response()->json(['message' => 'Your account isn’t linked to a member profile yet.'], 404);
        }

        return response()->json(new MemberResource($member));
    }

    public function updateMe(Request $request)
    {
        $member = $request->user()->member;
        if (! $member) {
            return response()->json(['message' => 'Your account isn’t linked to a member profile yet.'], 404);
        }

        $response = $this->update($request, $member->code);

        return $response->getStatusCode() === 200
            ? response()->json(['message' => 'Your details were updated', 'member' => new MemberResource($member->fresh('cell'))])
            : $response;
    }
}
