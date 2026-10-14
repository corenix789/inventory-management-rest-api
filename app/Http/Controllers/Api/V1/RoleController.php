<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected RoleService $roleService) {}

    /**
     * GET /api/v1/roles
     * List all roles (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $perPage = (int) $request->get('per_page', 15);

        $roles = $this->roleService->getPaginatedRoles($perPage, $filters);

        return $this->success(
            RoleResource::collection($roles)->response()->getData(true),
            'Roles retrieved successfully'
        );
    }

    /**
     * POST /api/v1/roles
     * Create a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated(), $request->user());

        return $this->created(new RoleResource($role), 'Role created successfully');
    }

    /**
     * GET /api/v1/roles/{role}
     * Show a single role.
     */
    public function show(int $role): JsonResponse
    {
        try {
            $roleModel = $this->roleService->getRoleById($role);

            return $this->success(new RoleResource($roleModel), 'Role retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Role not found');
        }
    }

    /**
     * PUT /api/v1/roles/{role}
     * Update an existing role.
     */
    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        try {
            $updated = $this->roleService->updateRole($role, $request->validated(), $request->user());

            return $this->success(new RoleResource($updated), 'Role updated successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Role not found');
        }
    }

    /**
     * DELETE /api/v1/roles/{role}
     * Delete a role.
     */
    public function destroy(int $role): JsonResponse
    {
        try {
            $this->roleService->deleteRole($role);

            return $this->deleted('Role deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Role not found');
        }
    }

    /**
     * POST /api/v1/roles/{role}/permissions
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, int $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        try {
            $this->roleService->syncPermissions($role, $request->permissions, $request->user());
            $updatedRole = $this->roleService->getRoleById($role);

            return $this->success(new RoleResource($updatedRole), 'Permissions synced successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Role not found');
        }
    }

    /**
     * GET /api/v1/roles/{role}/permissions
     * Get permissions for a role.
     */
    public function permissions(int $role): JsonResponse
    {
        try {
            $permissions = $this->roleService->getRolePermissions($role);

            return $this->success(
                PermissionResource::collection($permissions),
                'Role permissions retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Role not found');
        }
    }
}
