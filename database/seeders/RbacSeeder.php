<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $modules = [
                'dashboard' => ['view'],
                'users' => ['view', 'view-details', 'assign-roles'],
                'roles' => ['view', 'view-details', 'create', 'edit', 'delete', 'assign-permissions', 'assign-admin'],
                'permissions' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'categories' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'products' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'brands' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'units' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'taxes' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'warehouses' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'suppliers' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'customers' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'purchases' => ['view', 'view-details', 'create', 'edit', 'delete'],
                'purchase-details' => ['view', 'view-details'],
            ];

            $permissions = [];
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action) {
                    $name = $module.'.'.$action;
                    $permissions[$name] = Permission::query()->updateOrCreate(
                        ['name' => $name],
                        [
                            'slug' => Str::slug($name),
                            'display_name' => ucfirst(str_replace('-', ' ', $action)).' '.ucfirst(str_replace('-', ' ', $module)),
                            'module' => $module,
                            'description' => ucfirst($action).' access to the '.str_replace('-', ' ', $module).' module.',
                            'status' => true,
                        ],
                    );
                }
            }

            $all = collect($permissions)->pluck('id')->all();
            $inventory = array_merge(
                $this->permissionIds($permissions, ['dashboard'], ['view']),
                $this->permissionIds($permissions, [
                    'categories', 'products', 'brands', 'units', 'taxes', 'warehouses',
                ], ['view', 'view-details', 'create', 'edit', 'delete']),
            );
            $purchasing = $this->permissionIds($permissions, [
                'dashboard', 'warehouses', 'suppliers', 'customers', 'purchases', 'purchase-details',
            ], ['view', 'view-details', 'create', 'edit', 'delete']);
            $viewer = $this->permissionIds($permissions, [
                'dashboard', 'categories', 'products', 'brands', 'units', 'taxes',
                'warehouses', 'suppliers', 'customers', 'purchases', 'purchase-details',
            ], ['view', 'view-details']);

            $roles = [
                'admin' => ['Administrator', 'Full access to all application modules.', $all],
                'inventory-manager' => ['Inventory Manager', 'Manage inventory catalog and warehouse modules.', $inventory],
                'purchase-manager' => ['Purchase Manager', 'Manage suppliers, customers, purchases, and purchase details.', $purchasing],
                'viewer' => ['Viewer', 'Read-only access to inventory and purchasing information.', $viewer, true],
            ];

            foreach ($roles as $slug => $roleDefinition) {
                [$displayName, $description, $permissionIds] = $roleDefinition;
                $isDefault = $roleDefinition[3] ?? false;
                $role = Role::withTrashed()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $slug,
                        'display_name' => $displayName,
                        'description' => $description,
                        'status' => true,
                        'is_default' => $isDefault,
                    ],
                );
                if ($role->trashed()) {
                    $role->restore();
                }
                $role->permissions()->sync($permissionIds);
            }
        });
    }

    private function permissionIds(array $permissions, array $modules, array $actions): array
    {
        $ids = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $name = $module.'.'.$action;
                if (isset($permissions[$name])) {
                    $ids[] = $permissions[$name]->id;
                }
            }
        }

        return $ids;
    }
}
