<?php

namespace Tests\Feature;

use App\Models\{Branch, Category, Inventory, Product, Transfer, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/movements/create')->assertRedirect(route('login'));
    }

    public function test_vendor_can_view_catalog_but_cannot_manage_it(): void
    {
        $user = User::factory()->create(['role' => 'vendedor']);
        $category = Category::create(['name' => 'Catálogo', 'slug' => 'catalogo']);
        $product = Product::create(['category_id' => $category->id, 'internal_code' => 'CAT-001', 'name' => 'Producto visible', 'purchase_price' => 1, 'sale_price' => 2, 'minimum_stock' => 0]);
        $branch = Branch::create(['name' => 'Sede vendedor', 'slug' => 'sede-vendedor']);
        $user->branches()->attach($branch);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Producto visible');
    }

    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_password_is_required_to_delete_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $target = User::factory()->create(['role' => 'vendedor']);
        $this->actingAs($admin);

        $this->delete(route('users.destroy', $target), ['password' => 'incorrecta'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);

        $this->delete(route('users.destroy', $target), ['password' => 'password'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
    }

    public function test_entry_and_exit_update_stock_without_going_negative(): void
    {
        [$user, $product, $branch] = $this->inventoryContext();
        $this->actingAs($user);

        $this->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $branch->id,
            'type' => 'entrada', 'quantity' => 10, 'movement_date' => now()->toDateString(),
        ])->assertRedirect(route('movements.index'));
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $this->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $branch->id,
            'type' => 'salida', 'quantity' => 11, 'movement_date' => now()->toDateString(),
        ])->assertStatus(422);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);
    }

    public function test_vendor_cannot_register_movement_in_an_unassigned_branch(): void
    {
        [$user, $product, $branch] = $this->inventoryContext();
        $otherBranch = Branch::create(['name' => 'No autorizada', 'slug' => 'no-autorizada']);

        $this->actingAs($user)->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $otherBranch->id,
            'type' => 'entrada', 'quantity' => 1, 'movement_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_transfer_is_completed_once_and_duplicate_pending_transfer_is_rejected(): void
    {
        [$user, $product, $origin] = $this->inventoryContext();
        $destination = Branch::create(['name' => 'Destino', 'slug' => 'destino']);
        $user->branches()->attach($destination);
        Inventory::where(['product_id' => $product->id, 'branch_id' => $origin->id])->update(['quantity' => 8]);
        $this->actingAs($user);
        $payload = ['product_id' => $product->id, 'from_branch_id' => $origin->id, 'to_branch_id' => $destination->id, 'quantity' => 3];

        $this->post(route('transfers.store'), $payload)->assertRedirect(route('transfers.index'));
        $this->post(route('transfers.store'), $payload)->assertSessionHasErrors('quantity');
        $transfer = Transfer::firstOrFail();
        $this->post(route('transfers.complete', $transfer))->assertRedirect();
        $this->post(route('transfers.complete', $transfer))->assertStatus(422);

        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $origin->id, 'quantity' => 5]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $destination->id, 'quantity' => 3]);
    }

    private function inventoryContext(): array
    {
        $user = User::factory()->create(['role' => 'vendedor']);
        $category = Category::create(['name' => 'General', 'slug' => 'general']);
        $product = Product::create(['category_id' => $category->id, 'internal_code' => 'GEN-001', 'name' => 'Producto', 'purchase_price' => 1, 'sale_price' => 2, 'minimum_stock' => 0]);
        $branch = Branch::create(['name' => 'Origen', 'slug' => 'origen']);
        $user->branches()->attach($branch);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 0]);

        return [$user, $product, $branch];
    }
}
