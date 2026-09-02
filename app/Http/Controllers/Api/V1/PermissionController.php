<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PermissionService $permissionService) {}

    /**
     * GET /api/v1/permissions
     * List all permissions (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'status', 'module']);
            $perPage = (int) $request->get('per_page', 50);

            $permissions = $this->permissionService->getPaginatedPermissions($perPage, $filters);

            return $this->success(
                PermissionResource::collection($permissions)->response()->getData(true),
                'Permissions retrieved successfully'
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve permissions', 500);
        }
    }

    /**
     * POST /api/v1/permissions
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->permissionService->createPermission($request->validated());
            return $this->created(new PermissionResource($permission), 'Permission created successfully');
        } catch (\Throwable $e) {
            return $this->error('Failed to create permission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/permissions/{permission}
     * Show a single permission.
     */
    public function show(int $permission): JsonResponse
    {
        try {
            $perm = $this->permissionService->getPermissionById($permission);
            return $this->success(new PermissionResource($perm), 'Permission retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('Permission not found');
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve permission', 500);
        }
    }

    /**
     * PUT /api/v1/permissions/{permission}
     * Update an existing permission.
     */
    public function update(UpdatePermissionRequest $request, int $permission): JsonResponse
    {
        try {
            $updated = $this->permissionService->updatePermission($permission, $request->validated());
            return $this->success(new PermissionResource($updated), 'Permission updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('Permission not found');
        } catch (\Throwable $e) {
            return $this->error('Failed to update permission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/permissions/{permission}
     * Delete a permission.
     */
    public function destroy(int $permission): JsonResponse
    {
        try {
            $this->permissionService->deletePermission($permission);
            return $this->deleted('Permission deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('Permission not found');
        } catch (\Throwable $e) {
            return $this->error('Failed to delete permission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/permissions/grouped
     * Get permissions grouped by module (for UI checkbox grids).
     */
    public function grouped(): JsonResponse
    {
        try {
            $grouped = $this->permissionService->getGroupedByModule();
            $result = $grouped->map(fn ($perms) => PermissionResource::collection($perms));
            return $this->success($result, 'Grouped permissions retrieved successfully');
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve grouped permissions', 500);
        }
    }
}
