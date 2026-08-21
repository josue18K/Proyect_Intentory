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

    public function test_vendor_cannot_manage_catalog(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'vendedor']))
            ->get(route('products.index'))
            ->assertForbidden();
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

    public function test_entry_and_exit_update_stock_without_going_negative(): void
    {
        [$user, $product, $branch] = $this->inventoryContext();
        $this->actingAs($user);

        $this->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $branch->id,
            'type' => 'entrada', 'quantity' => 10,
        ])->assertRedirect(route('movements.index'));
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        $this->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $branch->id,
            'type' => 'salida', 'quantity' => 11,
        ])->assertStatus(422);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);
    }

    public function test_vendor_cannot_register_movement_in_an_unassigned_branch(): void
    {
        [$user, $product, $branch] = $this->inventoryContext();
        $otherBranch = Branch::create(['name' => 'No autorizada', 'slug' => 'no-autorizada']);

        $this->actingAs($user)->post(route('movements.store'), [
            'product_id' => $product->id, 'branch_id' => $otherBranch->id,
            'type' => 'entrada', 'quantity' => 1,
        ])->assertForbidden();
    }

    public function test_transfer_is_completed_once_and_duplicate_pending_transfer_is_rejected(): void
    {
        [$user, $product, $origin] = $this->inventoryContext();
        $destination = Branch::create(['name' => 'Destino', 'slug' => 'destino']);
        $user->branches()->attach($destination);
        Inventory::create(['product_id' => $product->id, 'branch_id' => $origin->id, 'quantity' => 8]);
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

        return [$user, $product, $branch];
    }
}
