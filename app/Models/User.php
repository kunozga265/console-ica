<?php

namespace App\Models;

use App\Support\Scopes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /** @var array<int, string>|null */
    private ?array $scopeCache = null;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function fullName()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function cell()
    {
        return $this->hasOne(Cell::class);
    }

    public function highlights()
    {
        return $this->hasMany(Highlight::class);
    }
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function usages()
    {
        return $this->belongsToMany(Usage::class, 'usage_user', 'user_id', 'usage_id');
    }

    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
        } else {
            if ($this->hasRole($roles)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole($role)
    {
        if ($this->roles()->where('name', $role)->first()) {
            return true;
        }
        return false;
    }

    public function isSuper(): bool
    {
        return $this->roles->contains('name', 'super');
    }

    /** Scopes this user may manage (App\Support\Scopes); super users have all of them. */
    public function scopes(): array
    {
        if ($this->isSuper()) {
            return Scopes::keys();
        }

        return $this->scopeCache ??= array_values(array_intersect(
            Scopes::keys(),
            DB::table('user_scopes')->where('user_id', $this->id)->pluck('scope')->all()
        ));
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes(), true);
    }

    public function hasAnyScope(string ...$scopes): bool
    {
        return $scopes ? (bool) array_intersect($scopes, $this->scopes()) : $this->scopes() !== [];
    }

    /** Replaces the user's scopes (and bumps updated_at so the app's sync sends them). */
    public function syncScopes(array $scopes): void
    {
        $scopes = array_values(array_intersect(Scopes::keys(), $scopes));
        DB::transaction(function () use ($scopes) {
            DB::table('user_scopes')->where('user_id', $this->id)->whereNotIn('scope', $scopes)->delete();
            foreach ($scopes as $scope) {
                DB::table('user_scopes')->insertOrIgnore(['user_id' => $this->id, 'scope' => $scope, 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->touch();
        });
        $this->scopeCache = null;
    }



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "avatar",
        "first_name",
        "middle_name",
        "other_name",
        "last_name",
        "member_id",
        'email',
        'password',
        'phone_number_airtel',
        'phone_number_tnm',
        'phone_number_international',
        'trigger',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
