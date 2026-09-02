<?php

namespace App\Repositories;

use App\Interfaces\RoleRepositoryInterface;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(protected Role $model) {}

    /**
     * Get all roles (optionally filtered).
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->model->with('permissions');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('display_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->get();
    }

    /**
     * Get paginated roles.
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with('permissions');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('display_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find role by ID (with permissions eager-loaded).
     */
    public function findById(int $id): Role
    {
        return $this->model->with('permissions')->findOrFail($id);
    }

    /**
     * Find role by slug.
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->model->with('permissions')->where('slug', $slug)->first();
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing role.
     */
    public function update(int $id, array $data): Role
    {
        $role = $this->findById($id);
        $role->update($data);
        return $role->fresh('permissions');
    }

    /**
     * Delete a role (soft-delete).
     */
    public function delete(int $id): bool
    {
        $role = $this->findById($id);
        return $role->delete();
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $role = $this->findById($roleId);
        $role->syncPermissions($permissionIds);
    }

    /**
     * Get permissions for a role.
     */
    public function getPermissions(int $roleId): Collection
    {
        return $this->findById($roleId)->permissions;
    }
}
