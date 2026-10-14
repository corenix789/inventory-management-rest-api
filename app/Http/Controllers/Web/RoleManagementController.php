<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly RoleAssignmentService $roleAssignmentService,
    ) {}

    public function index(): View
    {
        return view('dashboard.roles.index', [
            'roles' => Role::withCount('users')->with('permissions')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.roles.form', [
            'role' => new Role,
            'permissions' => $this->permissionsByModule(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedRole($request);
        $role = $this->roleService->createRole($validated, $request->user());

        return redirect()->route('dashboard.roles.show', $role)->with('success', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        return view('dashboard.roles.show', [
            'role' => $role->load(['permissions', 'users']),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('dashboard.roles.form', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissionsByModule(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $this->validatedRole($request, $role);
        $role = $this->roleService->updateRole($role->id, $validated, $request->user());

        return redirect()->route('dashboard.roles.show', $role)->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roleService->deleteRole($role->id);

        return redirect()->route('dashboard.roles.index')->with('success', 'Role deleted successfully.');
    }

    public function users(): View
    {
        return view('dashboard.users', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
            'roles' => Role::active()->orderBy('display_name')->get(),
        ]);
    }

    public function syncUserRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
            'roles_present' => ['required', 'accepted'],
        ]);

        if ($request->boolean('roles_present') && ! array_key_exists('roles', $validated)) {
            $validated['roles'] = [];
        }

        $this->roleAssignmentService->sync($user, $request->user(), $validated['roles']);

        return back()->with('success', 'User roles updated successfully.');
    }

    private function permissionsByModule()
    {
        return Permission::active()->orderBy('module')->orderBy('name')->get()->groupBy('module');
    }

    private function validatedRole(Request $request, ?Role $role = null): array
    {
        $id = $role?->id;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($id)],
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
            'permissions_present' => ['sometimes', 'accepted'],
        ]);

        if ($request->boolean('permissions_present') && ! array_key_exists('permissions', $validated)) {
            $validated['permissions'] = [];
        }

        unset($validated['permissions_present']);

        return $validated;
    }
}
