<?php

namespace Database\Seeders;

use App\Models\{Branch, Category, Inventory, InventoryMovement, Product, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create(['name' => 'Josué Administrador', 'email' => 'admin@liuva.test', 'role' => 'administrador']);
        $categories = collect(['Perfumería', 'Maquillaje', 'Accesorios'])->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name, 'slug' => str($name)->slug()])]);
        $branches = collect(['Sede Principal', 'Liuva Mujer'])->mapWithKeys(fn ($name) => [$name => Branch::create(['name' => $name, 'slug' => str($name)->slug()])]);
        $products = [
            ['name' => 'Perfume Euphoria', 'internal_code' => 'PER-001', 'category_id' => $categories['Perfumería']->id, 'purchase_price' => 45, 'sale_price' => 79, 'minimum_stock' => 10],
            ['name' => 'Labial Matte 24H', 'internal_code' => 'MAQ-001', 'category_id' => $categories['Maquillaje']->id, 'purchase_price' => 8, 'sale_price' => 18, 'minimum_stock' => 12],
            ['name' => 'Collar Minimal', 'internal_code' => 'ACC-001', 'category_id' => $categories['Accesorios']->id, 'purchase_price' => 12, 'sale_price' => 30, 'minimum_stock' => 5],
        ];
        foreach ($products as $attributes) {
            $product = Product::create($attributes);
            foreach ($branches as $branch) Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => $product->internal_code === 'PER-001' && $branch->name === 'Sede Principal' ? 0 : 20]);
        }
        InventoryMovement::create(['product_id' => Product::first()->id, 'branch_id' => $branches['Sede Principal']->id, 'user_id' => $admin->id, 'type' => 'entrada', 'quantity' => 20, 'stock_before' => 0, 'stock_after' => 20, 'reason' => 'Carga inicial']);
        Inventory::where(['product_id' => Product::first()->id, 'branch_id' => $branches['Sede Principal']->id])->update(['quantity' => 20]);
    }
}
