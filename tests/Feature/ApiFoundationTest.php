<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Sanctum::actingAs($this->userWithRole('admin'));
    }

    public function test_category_validation_and_missing_records_use_expected_status_codes(): void
    {
        $this->postJson('/api/v1/categories', ['name' => ''])
            ->assertUnprocessable();

        $this->getJson('/api/v1/categories/999999')->assertNotFound();
        $this->getJson('/api/v1/warehouses/999999')->assertNotFound();
        $this->getJson('/api/v1/customers/999999')->assertNotFound();
        $this->getJson('/api/v1/suppliers/999999')->assertNotFound();
    }

    public function test_product_create_failure_rolls_back_the_product_insert(): void
    {
        $category = Category::create([
            'name' => 'Rollback Category',
            'slug' => 'rollback-category',
            'status' => true,
        ]);
        $payload = [
            'category_id' => $category->id,
            'name' => 'Duplicate Product',
            'purchase_price' => 5,
            'selling_price' => 10,
            'quantity' => 1,
            'status' => true,
        ];

        $this->postJson('/api/v1/products', $payload)->assertCreated();
        $this->postJson('/api/v1/products', $payload)->assertServerError();

        $this->assertDatabaseCount('products', 1);
    }

    public function test_admin_can_complete_product_crud(): void
    {
        $category = Category::create([
            'name' => 'CRUD Category',
            'slug' => 'crud-category',
        ]);
        $payload = [
            'category_id' => $category->id,
            'name' => 'CRUD Product',
            'purchase_price' => 5,
            'selling_price' => 10,
            'quantity' => 1,
            'status' => true,
        ];
        $created = $this->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'CRUD Product');
        $productId = $created->json('data.id');

        $this->getJson("/api/v1/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.id', $productId);
        $this->putJson("/api/v1/products/{$productId}", ['name' => 'Updated Product'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');
        $this->deleteJson("/api/v1/products/{$productId}")->assertOk();

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_product_rejects_unknown_optional_relationship_ids(): void
    {
        $category = Category::create([
            'name' => 'Validation Category',
            'slug' => 'validation-category',
        ]);

        $this->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'brand_id' => 999999,
            'name' => 'Invalid Relationship Product',
            'purchase_price' => 5,
            'selling_price' => 10,
            'quantity' => 1,
            'status' => true,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_relations_resolve_and_are_eager_loadable(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Linked Category', 'slug' => 'linked-category']);
        $brand = Brand::create(['name' => 'Linked Brand', 'slug' => 'linked-brand']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $tax = Tax::create(['name' => 'Sales Tax', 'rate' => 5, 'type' => 'percentage']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'MAIN']);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'tax_id' => $tax->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'name' => 'Related Product',
            'slug' => 'related-product',
            'sku' => 'RELATED-001',
            'selling_price' => 10,
        ]);

        $loadedProduct = Product::with([
            'category',
            'brand',
            'unit',
            'tax',
            'warehouse',
            'createdBy',
        ])->findOrFail($product->id);

        $this->assertTrue($loadedProduct->category->is($category));
        $this->assertTrue($loadedProduct->brand->is($brand));
        $this->assertTrue($loadedProduct->unit->is($unit));
        $this->assertTrue($loadedProduct->tax->is($tax));
        $this->assertTrue($loadedProduct->warehouse->is($warehouse));
        $this->assertTrue($loadedProduct->createdBy->is($user));
        $this->assertTrue($brand->products->contains($product));
    }

    public function test_purchase_response_uses_the_supplier_company_name(): void
    {
        $supplier = Supplier::create([
            'company_name' => 'Sample Supplier Company',
            'contact_person' => 'Supplier Contact',
        ]);
        $warehouse = Warehouse::create(['name' => 'Purchase Warehouse', 'code' => 'PURCHASE']);
        Purchase::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_number' => 'PUR-TEST-001',
            'purchase_date' => '2026-10-16',
        ]);

        $this->getJson('/api/v1/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.supplier.name', 'Sample Supplier Company');
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->sync([Role::where('slug', $slug)->value('id')]);

        return $user;
    }
}
