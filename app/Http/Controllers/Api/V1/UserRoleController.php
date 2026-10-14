<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function __construct(private readonly RoleAssignmentService $roleAssignmentService) {}

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($user->roles()->with('permissions')->get()),
        ]);
    }

    public function sync(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);

        $this->roleAssignmentService->sync($user, $request->user(), $validated['roles']);

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data' => RoleResource::collection($user->roles()->with('permissions')->get()),
        ]);
    }
}
