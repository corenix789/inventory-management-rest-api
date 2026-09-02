<?php

namespace App\Repositories;

use App\Interfaces\PermissionRepositoryInterface;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(protected Permission $model) {}

    /**
     * Get all permissions (optionally filtered).
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('display_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('module', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('module')->orderBy('name')->get();
    }

    /**
     * Get paginated permissions.
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('display_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('module', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('module')->orderBy('name')->paginate($perPage);
    }

    /**
     * Find permission by ID.
     */
    public function findById(int $id): Permission
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find permission by slug.
     */
    public function findBySlug(string $slug): ?Permission
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Create a new permission.
     */
    public function create(array $data): Permission
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing permission.
     */
    public function update(int $id, array $data): Permission
    {
        $permission = $this->findById($id);
        $permission->update($data);
        return $permission->fresh();
    }

    /**
     * Delete a permission.
     */
    public function delete(int $id): bool
    {
        $permission = $this->findById($id);
        return $permission->delete();
    }

    /**
     * Get all permissions for a specific module.
     */
    public function getByModule(string $module): Collection
    {
        return $this->model->where('module', $module)->orderBy('name')->get();
    }

    /**
     * Get permissions grouped by module (for UI display).
     */
    public function getGroupedByModule(): \Illuminate\Support\Collection
    {
        return $this->model->orderBy('module')->orderBy('name')
            ->get()
            ->groupBy('module');
    }
}
