<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request,)
    {
        $users = User::orderBy("first_name")->get();

        return Inertia::render("Users/Index", [
            "users" => UserResource::collection($users),
        ]);
    }

    public function changeAdminRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->hasRole('super')) {
            
            if ($user->hasRole('admin')) {
                $name = 'normal';
            } else {
                $name = 'admin';
            }
            
            $user->roles()->detach();
            $role = Role::where('name', $name)->first();
            $user->roles()->attach($role);
        }

        return Redirect::back()->with("success", "Updated user role");
    }
}
