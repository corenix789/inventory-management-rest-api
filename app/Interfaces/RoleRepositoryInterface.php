<?php

namespace App\Interfaces;

interface RoleRepositoryInterface
{
    public function getAll(array $filters = []);

    public function findById(int $id);

    public function findBySlug(string $slug);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id): bool;

    public function syncPermissions(int $roleId, array $permissionIds): void;

    public function getPermissions(int $roleId);

    public function getPaginated(int $perPage = 15, array $filters = []);
}
