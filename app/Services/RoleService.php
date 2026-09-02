<?php

namespace App\Services;

use App\Interfaces\RoleRepositoryInterface;

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
    public function createRole(array $data)
    {
        $role = $this->roleRepository->create($data);

        // Sync permissions if provided
        if (! empty($data['permissions'])) {
            $this->roleRepository->syncPermissions($role->id, $data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update an existing role.
     */
    public function updateRole(int $id, array $data)
    {
        $role = $this->roleRepository->update($id, $data);

        // Sync permissions if provided (null = don't change, [] = remove all)
        if (array_key_exists('permissions', $data)) {
            $this->roleRepository->syncPermissions($id, $data['permissions'] ?? []);
        }

        return $role->load('permissions');
    }

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): bool
    {
        return $this->roleRepository->delete($id);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->roleRepository->syncPermissions($roleId, $permissionIds);
    }

    /**
     * Get permissions for a role.
     */
    public function getRolePermissions(int $roleId)
    {
        return $this->roleRepository->getPermissions($roleId);
    }
}
