<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_rbac_seeder_is_idempotent_and_does_not_assign_existing_users_an_admin_role(): void
    {
        $existingUser = User::factory()->create();
        $this->seed(RbacSeeder::class);

        $this->assertSame(4, Role::count());
        $this->assertSame(63, Permission::count());
        $this->assertFalse($existingUser->fresh()->hasRole('admin'));
        $this->assertTrue($existingUser->fresh()->hasRole('viewer'));
    }

    public function test_protected_api_requires_sanctum_authentication(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_admin_inventory_purchase_and_viewer_roles_have_expected_access(): void
    {
        $admin = $this->userWithRole('admin');
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/categories')->assertOk();

        $inventoryManager = $this->userWithRole('inventory-manager');
        Sanctum::actingAs($inventoryManager);
        $this->getJson('/api/v1/products')->assertOk();
        $this->getJson('/api/v1/purchases')->assertForbidden();

        $purchaseManager = $this->userWithRole('purchase-manager');
        Sanctum::actingAs($purchaseManager);
        $this->getJson('/api/v1/purchases')->assertOk();
        $this->getJson('/api/v1/products')->assertForbidden();

        $viewer = $this->userWithRole('viewer');
        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/products')->assertOk();
        $this->postJson('/api/v1/products', [])->assertForbidden();
        $this->deleteJson('/api/v1/products/1')->assertForbidden();
    }

    public function test_admin_can_perform_resource_crud_and_viewers_cannot_mutate(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));

        $created = $this->postJson('/api/v1/categories', [
            'name' => 'Kitchen',
            'description' => 'Kitchen inventory',
            'status' => true,
        ])->assertCreated();
        $categoryId = $created->json('data.id');

        $this->getJson("/api/v1/categories/{$categoryId}")->assertOk();
        $this->putJson("/api/v1/categories/{$categoryId}", [
            'name' => 'Office',
            'description' => 'Office inventory',
            'status' => true,
        ])->assertOk();
        $this->deleteJson("/api/v1/categories/{$categoryId}")->assertOk();

        Sanctum::actingAs($this->userWithRole('viewer'));
        $this->postJson('/api/v1/categories', [])->assertForbidden();
        $this->putJson('/api/v1/categories/1', [])->assertForbidden();
        $this->deleteJson('/api/v1/categories/1')->assertForbidden();
    }

    public function test_user_cannot_assign_themselves_the_administrator_role(): void
    {
        $actor = $this->userWithPermissions(['users.assign-roles']);
        $adminRoleId = Role::where('slug', 'admin')->value('id');
        Sanctum::actingAs($actor);

        $this->putJson("/api/v1/users/{$actor->id}/roles", ['roles' => [$adminRoleId]])
            ->assertForbidden();

        $this->assertFalse($actor->fresh()->hasRole('admin'));
    }

    public function test_role_editor_cannot_grant_a_permission_they_do_not_hold(): void
    {
        $actor = $this->userWithPermissions(['roles.edit', 'roles.assign-permissions']);
        $role = Role::where('slug', 'viewer')->firstOrFail();
        $createProductPermission = Permission::where('name', 'products.create')->value('id');
        Sanctum::actingAs($actor);

        $this->putJson("/api/v1/roles/{$role->id}", [
            'permissions' => [$createProductPermission],
        ])->assertForbidden();

        $this->assertFalse($role->fresh()->hasPermission('products.create'));
    }

    public function test_role_creation_generates_a_unique_slug_when_omitted(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));

        $this->postJson('/api/v1/roles', [
            'name' => 'Stock Auditor',
            'status' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'stock-auditor');
    }

    public function test_authorized_user_can_assign_a_role_within_their_permission_set(): void
    {
        $actor = $this->userWithPermissions(['users.assign-roles', 'products.view']);
        $target = User::factory()->create();
        $role = Role::create([
            'name' => 'catalog-reader',
            'slug' => 'catalog-reader',
            'display_name' => 'Catalog Reader',
            'status' => true,
        ]);
        $role->permissions()->sync([Permission::where('name', 'products.view')->value('id')]);
        Sanctum::actingAs($actor);

        $this->putJson("/api/v1/users/{$target->id}/roles", ['roles' => [$role->id]])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasRole('catalog-reader'));
    }

    public function test_dashboard_hides_create_actions_and_enforces_page_access(): void
    {
        $viewer = $this->userWithRole('viewer');
        $token = $viewer->createToken('dashboard_token')->plainTextToken;

        $this->withSession([
            'auth_token' => $token,
            'auth_user' => $viewer->toArray(),
        ])->get('/dashboard/products')
            ->assertOk()
            ->assertDontSee('onclick="openCreateModal()"', false);

        $this->withSession([
            'auth_token' => $token,
            'auth_user' => $viewer->toArray(),
        ])->get('/dashboard/roles')
            ->assertForbidden();
    }

    public function test_existing_roleless_user_gets_viewer_access_after_dashboard_login(): void
    {
        $user = User::factory()->create(['password' => 'safe-test-password']);
        $csrfToken = 'dashboard-login-csrf';

        $this->withSession(['_token' => $csrfToken])
            ->post('/dashboard/login', [
                '_token' => $csrfToken,
                'email' => $user->email,
                'password' => 'safe-test-password',
            ])
            ->assertRedirect('/dashboard/home');

        $this->assertTrue($user->fresh()->hasRole('viewer'));

        $this->get('/dashboard/home')->assertOk();
    }

    public function test_admin_can_render_role_permission_matrix_and_user_assignments(): void
    {
        $admin = $this->userWithRole('admin');
        $token = $admin->createToken('dashboard_token')->plainTextToken;
        $session = [
            'auth_token' => $token,
            'auth_user' => $admin->toArray(),
        ];

        $this->withSession($session)->get('/dashboard/roles/create')
            ->assertOk()
            ->assertSee('Select all permissions')
            ->assertSee('Create Products');

        $this->withSession($session)->get('/dashboard/users')
            ->assertOk()
            ->assertSee($admin->email);
    }

    public function test_web_user_role_assignment_can_clear_all_roles(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('viewer');
        $token = $admin->createToken('dashboard_token')->plainTextToken;
        $csrfToken = 'rbac-csrf-token';
        $session = [
            'auth_token' => $token,
            'auth_user' => $admin->toArray(),
            '_token' => $csrfToken,
        ];

        $this->from('/dashboard/users')
            ->withSession($session)
            ->put("/dashboard/users/{$target->id}/roles", [
                '_token' => $csrfToken,
                'roles_present' => '1',
            ])
            ->assertRedirect('/dashboard/users');

        $this->assertCount(0, $target->fresh()->roles);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);

        return $user;
    }

    private function userWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'test-'.uniqid(),
            'slug' => 'test-'.uniqid(),
            'status' => true,
        ]);
        $role->permissions()->sync(Permission::whereIn('name', $permissionNames)->pluck('id'));
        $user->roles()->sync([$role->id]);

        return $user;
    }
}
