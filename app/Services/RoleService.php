<?php

namespace App\Services;

use App\Interfaces\RoleRepositoryInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository
    ) {}

    /**
     * Get all roles with optional filters.
     */
    public function getAllRoles(array $filters = [])
    {
        return $this->roleRepository->getAll($filters);
    }

    /**
     * Get paginated roles.
     */
    public function getPaginatedRoles(int $perPage = 15, array $filters = [])
    {
        return $this->roleRepository->getPaginated($perPage, $filters);
    }

    /**
     * Get a single role by ID.
     */
    public function getRoleById(int $id)
    {
        return $this->roleRepository->findById($id);
    }

    /**
     * Get a single role by slug.
     */
    public function getRoleBySlug(string $slug)
    {
        return $this->roleRepository->findBySlug($slug);
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data, ?User $actor = null)
    {
        return DB::transaction(function () use ($data, $actor) {
            $permissions = $data['permissions'] ?? null;
            unset($data['permissions']);
            $data = $this->ensureUniqueSlug($data);

            if ($permissions !== null) {
                $this->authorizePermissionChanges($actor, $permissions);
            }

            $role = $this->roleRepository->create($data);
            if ($permissions !== null) {
                $this->roleRepository->syncPermissions($role->id, $permissions);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Update an existing role.
     */
    public function updateRole(int $id, array $data, ?User $actor = null)
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $role = $this->roleRepository->findById($id);
            $hasPermissions = array_key_exists('permissions', $data);
            $permissions = $data['permissions'] ?? null;

            if ($role->slug === 'admin' && (isset($data['status']) && ! $data['status'])) {
                throw ValidationException::withMessages(['status' => 'The administrator role must remain active.']);
            }
            if ($role->slug === 'admin' && isset($data['slug']) && $data['slug'] !== 'admin') {
                throw ValidationException::withMessages(['slug' => 'The administrator role slug cannot be changed.']);
            }
            if (array_key_exists('slug', $data)) {
                $data = $this->ensureUniqueSlug($data, $id, $role->name);
            }

            if ($hasPermissions) {
                $this->authorizePermissionChanges($actor, $permissions ?? []);
                unset($data['permissions']);
            }

            $this->roleRepository->update($id, $data);
            if ($hasPermissions) {
                $this->roleRepository->syncPermissions($id, $permissions ?? []);
            }

            return $role->fresh('permissions');
        });
    }

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): bool
    {
        if ($this->roleRepository->findById($id)->slug === 'admin') {
            throw ValidationException::withMessages(['role' => 'The administrator role cannot be deleted.']);
        }

        return $this->roleRepository->delete($id);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(int $roleId, array $permissionIds, ?User $actor = null): void
    {
        DB::transaction(function () use ($roleId, $permissionIds, $actor) {
            $this->authorizePermissionChanges($actor, $permissionIds);
            $this->roleRepository->syncPermissions($roleId, $permissionIds);
        });
    }

    /**
     * Get permissions for a role.
     */
    public function getRolePermissions(int $roleId)
    {
        return $this->roleRepository->getPermissions($roleId);
    }

    private function authorizePermissionChanges(?User $actor, array $permissionIds): void
    {
        if (! $actor) {
            return;
        }

        if (! $actor->hasPermission('roles.assign-permissions')) {
            abort(403, 'You are not allowed to assign permissions to roles.');
        }

        $allowedIds = $actor->getAllPermissions()->pluck('id')->all();
        $requestedIds = Permission::query()->whereIn('id', $permissionIds)->pluck('id')->all();

        if (count($requestedIds) !== count(array_unique($permissionIds)) || array_diff($requestedIds, $allowedIds)) {
            abort(403, 'You cannot grant permissions that you do not hold.');
        }
    }

    private function ensureUniqueSlug(array $data, ?int $ignoreId = null, ?string $fallbackName = null): array
    {
        $slug = $data['slug'] ?? null;
        if (blank($slug)) {
            $slug = Str::slug($data['name'] ?? $fallbackName ?? '');
        }

        if (blank($slug)) {
            throw ValidationException::withMessages(['slug' => 'A valid role slug is required.']);
        }

        $query = Role::withTrashed()->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'The role slug is already in use.']);
        }

        $data['slug'] = $slug;

        return $data;
    }
}
