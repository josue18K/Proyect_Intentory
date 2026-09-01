<?php

namespace Tests\Feature;

use App\Models\{Branch, Category, Inventory, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialStockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_stock_api_calculates_dozens_and_whatsapp_message(): void
    {
        $admin = User::factory()->create(['role' => 'administrador', 'permissions' => ['reports.view']]);
        $branch = Branch::create(['name' => 'Pauza', 'slug' => 'pauza']);
        $category = Category::create(['name' => 'Limpieza', 'slug' => 'limpieza']);
        $product = Product::create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'internal_code' => 'TEST-001',
            'name' => 'Limpiatodo 1Lt',
            'sale_price' => 2.19,
            'minimum_stock' => 3,
            'report_group' => 'chemicals',
        ]);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 17]);
        $acid = Product::create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'internal_code' => 'TEST-002',
            'name' => 'Ácido 1Lt',
            'sale_price' => 3.99,
            'minimum_stock' => 3,
            'report_group' => 'chemicals',
        ]);
        Inventory::create(['product_id' => $acid->id, 'branch_id' => $branch->id, 'quantity' => 31]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/reports/special-stock?branch_id='.$branch->id);

        $response->assertOk()
            ->assertJsonPath('data.0.key', 'chemicals')
            ->assertJsonPath('data.0.products.0.quantity', 17)
            ->assertJsonPath('data.0.products.0.full_dozens', 1)
            ->assertJsonPath('data.0.products.0.remainder', 5)
            ->assertJsonPath('data.0.products.0.approx_dozens', 1.42)
            ->assertJsonPath('data.0.products.0.whatsapp_label', '1 ≈ 1 docena aprox (17uni)')
            ->assertJsonPath('data.0.message', fn ($message) => str_contains($message, 'Limpiatodo 1Lt: 1 ≈ 1 docena aprox (17uni)'))
            ->assertJsonPath('data.0.message', fn ($message) => str_contains($message, 'Ácido 1Lt: 3 ≈ 2 docenas aprox (31uni)'))
            ->assertJsonFragment(['dozen_label' => '1 docena + 5 unidades (≈ 1.42 docenas)']);
    }
}
