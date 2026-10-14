<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $filters = $request->only(['search', 'status', 'module']);
        $perPage = (int) $request->get('per_page', 50);

        $permissions = $this->permissionService->getPaginatedPermissions($perPage, $filters);

        return $this->success(
            PermissionResource::collection($permissions)->response()->getData(true),
            'Permissions retrieved successfully'
        );
    }

    /**
     * POST /api/v1/permissions
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->createPermission($request->validated());

        return $this->created(new PermissionResource($permission), 'Permission created successfully');
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
        } catch (ModelNotFoundException) {
            return $this->notFound('Permission not found');
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
        } catch (ModelNotFoundException) {
            return $this->notFound('Permission not found');
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
        } catch (ModelNotFoundException) {
            return $this->notFound('Permission not found');
        }
    }

    /**
     * GET /api/v1/permissions/grouped
     * Get permissions grouped by module (for UI checkbox grids).
     */
    public function grouped(): JsonResponse
    {
        $grouped = $this->permissionService->getGroupedByModule();
        $result = $grouped->map(fn ($perms) => PermissionResource::collection($perms));

        return $this->success($result, 'Grouped permissions retrieved successfully');
    }
}
