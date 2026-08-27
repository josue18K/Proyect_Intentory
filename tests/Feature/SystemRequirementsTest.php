<?php

namespace Tests\Feature;

use App\Models\{Branch, Category, Inventory, InventoryMovement, Product, StockReview, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_vendor_assigned_to_one_store(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $branch = Branch::create(['name' => 'Tienda Norte', 'slug' => 'tienda-norte']);
        $this->actingAs($admin)->post(route('users.store'), ['name' => 'Vendedor', 'email' => 'venta@test.local', 'password' => 'segura123', 'password_confirmation' => 'segura123', 'role' => 'vendedor', 'branch_id' => $branch->id])->assertRedirect(route('users.index'));
        $seller = User::whereEmail('venta@test.local')->firstOrFail();
        $this->assertSame([$branch->id], $seller->branches()->pluck('branches.id')->all());
    }

    public function test_same_internal_code_is_allowed_in_different_stores(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $branches = collect(['Norte', 'Sur'])->map(fn ($name) => Branch::create(['name' => $name, 'slug' => strtolower($name)]));
        foreach ($branches as $branch) $this->actingAs($admin)->post(route('products.store'), ['branch_id' => $branch->id, 'category_id' => $category->id, 'internal_code' => 'SKU-001', 'name' => 'Producto '.$branch->name, 'sale_price' => 10, 'minimum_stock' => 2, 'initial_quantity' => 5, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, Product::where('internal_code', 'SKU-001')->count());
        $this->assertSame(10, Inventory::sum('quantity'));
    }

    public function test_stock_review_and_pdf_report_work_for_assigned_store(): void
    {
        $seller = User::factory()->create(['role' => 'vendedor', 'permissions' => ['inventory.view', 'inventory.manage', 'reports.view']]);
        $branch = Branch::create(['name' => 'Principal', 'slug' => 'principal']);
        $seller->branches()->attach($branch);
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $product = Product::create(['branch_id' => $branch->id, 'category_id' => $category->id, 'internal_code' => 'P-1', 'name' => 'Producto', 'sale_price' => 10, 'minimum_stock' => 3]);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 1]);
        $this->actingAs($seller)->post(route('stock-reviews.store'), ['branch_id' => $branch->id])->assertRedirect();
        $this->assertDatabaseHas('stock_reviews', ['branch_id' => $branch->id, 'low_stock_count' => 1]);
        $this->get(route('reports.pdf', ['branch_id' => $branch->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_stock_control_searches_products_by_barcode_without_old_filters(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $branch = Branch::create(['name' => 'Principal', 'slug' => 'principal']);
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $product = Product::create(['branch_id' => $branch->id, 'category_id' => $category->id, 'internal_code' => 'BUS-01', 'barcode' => '7750001234567', 'name' => 'Producto buscable', 'sale_price' => 5, 'minimum_stock' => 1]);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 2]);
        InventoryMovement::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'user_id' => $admin->id, 'type' => 'entrada', 'quantity' => 2, 'stock_before' => 0, 'stock_after' => 2, 'movement_date' => today()]);

        $this->actingAs($admin)->get(route('movements.index', ['search' => '7750001234567']))
            ->assertOk()
            ->assertSee('Producto buscable')
            ->assertSee('Buscar producto')
            ->assertDontSee('Todas las sedes');
    }
}
