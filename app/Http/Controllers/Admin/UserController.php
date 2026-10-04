<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Who can sign in, and who is an admin. */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');

        $page = User::with(['roles', 'member:id,avatar,code'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($role, ['admin', 'super']), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => collect($page->items())->map(fn (User $u) => [
                'id'     => $u->id,
                'name'   => $u->fullName(),
                'email'  => $u->email,
                'avatar' => $u->member?->avatar,
                'member' => $u->member?->code,
                'roles'  => $u->roles->pluck('name')->unique()->values(),
                'joined' => $u->created_at?->getTimestamp() * 1000,
            ]),
            'paging'  => ['page' => $page->currentPage(), 'lastPage' => $page->lastPage(), 'total' => $page->total()],
            'filters' => ['search' => $search, 'role' => $role],
            'counts'  => [
                'users'  => User::count(),
                'admins' => User::whereHas('roles', fn ($r) => $r->where('name', 'admin'))->count(),
                'supers' => User::whereHas('roles', fn ($r) => $r->where('name', 'super'))->count(),
            ],
        ]);
    }

    /** Grant or revoke admin (as the old console did). Super users can't be changed here. */
    public function toggleAdmin(Request $request, User $user)
    {
        if ($user->hasRole('super')) {
            return back()->withErrors(['role' => 'Super users can’t be changed here.']);
        }
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'You can’t change your own role.']);
        }

        $makeAdmin = ! $user->hasRole('admin');
        $user->roles()->detach();
        $user->roles()->attach(Role::where('name', $makeAdmin ? 'admin' : 'normal')->value('id'));

        return back()->with('success', $makeAdmin ? "{$user->fullName()} is now an admin" : "{$user->fullName()} is no longer an admin");
    }
}
