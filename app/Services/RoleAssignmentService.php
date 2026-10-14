<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleAssignmentService
{
    public function sync(User $target, User $actor, array $roleIds): void
    {
        if (! $actor->hasPermission('users.assign-roles')) {
            abort(403, 'You are not allowed to assign roles to users.');
        }

        DB::transaction(function () use ($target, $actor, $roleIds) {
            $roles = Role::query()
                ->active()
                ->whereIn('id', $roleIds)
                ->with('permissions')
                ->get();

            if ($roles->count() !== count(array_unique($roleIds))) {
                throw ValidationException::withMessages(['roles' => 'One or more selected roles are unavailable.']);
            }

            $actorPermissionIds = $actor->getAllPermissions()->pluck('id')->all();
            foreach ($roles as $role) {
                if ($role->slug === 'admin' && ! $actor->hasPermission('roles.assign-admin')) {
                    abort(403, 'You are not allowed to assign the administrator role.');
                }

                if ($role->permissions->pluck('id')->diff($actorPermissionIds)->isNotEmpty()) {
                    abort(403, 'You cannot assign a role with permissions you do not hold.');
                }
            }

            if ($target->is($actor) && $actor->hasRole('admin') && ! $roles->contains('slug', 'admin')) {
                $otherAdministrators = Role::query()->where('slug', 'admin')
                    ->whereHas('users', fn ($query) => $query->where('users.id', '!=', $actor->id))
                    ->exists();

                if (! $otherAdministrators) {
                    abort(422, 'You cannot remove the last administrator assignment from your own account.');
                }
            }

            $target->roles()->sync($roles->modelKeys());
        });
    }
}
