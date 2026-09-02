<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    /**
     * The roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
                    ->withTimestamps();
    }

    // ─────────────────────────────────────────────────────────────
    // Role helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Check if user has a specific role (by slug or name).
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
                    ->where('slug', $role)
                    ->orWhere('name', $role)
                    ->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
                    ->whereIn('slug', $roles)
                    ->orWhereIn('name', $roles)
                    ->exists();
    }

    /**
     * Check if user has a specific permission (through their roles).
     */
    public function hasPermission(string $permission): bool
    {
        // Admin bypass — admin role has all permissions
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->roles()
                    ->whereHas('permissions', function ($query) use ($permission) {
                        $query->where('slug', $permission)
                              ->orWhere('name', $permission);
                    })
                    ->exists();
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (! $this->hasPermission($perm)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|int $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->orWhere('name', $role)->firstOrFail();
        }
        $this->roles()->syncWithoutDetaching([$role instanceof Role ? $role->id : $role]);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string|int $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->orWhere('name', $role)->first();
        }
        if ($role) {
            $this->roles()->detach($role instanceof Role ? $role->id : $role);
        }
    }

    /**
     * Get all permission slugs for this user (through their roles).
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles()
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn ($role) => $role->permissions)
                    ->unique('id')
                    ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // Notifications (keep existing)
    // ─────────────────────────────────────────────────────────────

    public function sendPasswordResetNotification($token): void
    {
        logger('Custom Notification Called');
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
