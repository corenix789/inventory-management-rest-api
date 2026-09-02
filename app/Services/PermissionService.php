<?php

namespace App\Services;

use App\Interfaces\PermissionRepositoryInterface;

class PermissionService
{
    public function __construct(
        protected PermissionRepositoryInterface $permissionRepository
    ) {}

    /**
     * Get all permissions with optional filters.
     */
    public function getAllPermissions(array $filters = [])
    {
        return $this->permissionRepository->getAll($filters);
    }

    /**
     * Get paginated permissions.
     */
    public function getPaginatedPermissions(int $perPage = 15, array $filters = [])
    {
        return $this->permissionRepository->getPaginated($perPage, $filters);
    }

    /**
     * Get a single permission by ID.
     */
    public function getPermissionById(int $id)
    {
        return $this->permissionRepository->findById($id);
    }

    /**
     * Get a single permission by slug.
     */
    public function getPermissionBySlug(string $slug)
    {
        return $this->permissionRepository->findBySlug($slug);
    }

    /**
     * Create a new permission.
     */
    public function createPermission(array $data)
    {
        return $this->permissionRepository->create($data);
    }

    /**
     * Update an existing permission.
     */
    public function updatePermission(int $id, array $data)
    {
        return $this->permissionRepository->update($id, $data);
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(int $id): bool
    {
        return $this->permissionRepository->delete($id);
    }

    /**
     * Get permissions by module.
     */
    public function getByModule(string $module)
    {
        return $this->permissionRepository->getByModule($module);
    }

    /**
     * Get all permissions grouped by module (for UI checkbox lists).
     */
    public function getGroupedByModule()
    {
        return $this->permissionRepository->getGroupedByModule();
    }
}
