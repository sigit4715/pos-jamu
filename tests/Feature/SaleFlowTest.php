<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_record_sale_and_stock_is_reduced(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'JMU-TEST', 'name' => 'Jamu Uji', 'price' => 10000, 'stock' => 5, 'unit' => 'botol', 'is_active' => true]);

        $response = $this->actingAs($cashier)->post(route('sales.store'), ['payment_method' => 'cash', 'paid_amount' => 25000, 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['cashier_id' => $cashier->id, 'total' => 20000]);
        $this->assertDatabaseHas('sale_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_logs', ['product_id' => $product->id, 'type' => 'sale', 'stock_after' => 3]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_cashier_cannot_open_admin_product_page(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
    }

    public function test_cashier_can_process_sale_return_and_restock_item(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'JMU-RETUR', 'name' => 'Jamu Retur', 'price' => 12000, 'stock' => 3, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($cashier)->post(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 12000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $saleItem = \App\Models\SaleItem::query()->latest('id')->firstOrFail();
        $this->actingAs($cashier)->post(route('sale-returns.store'), [
            'sale_item_id' => [$saleItem->id],
            'quantity' => [$saleItem->id => 1],
            'restock' => [$saleItem->id],
            'reason' => 'Kemasan rusak',
        ])->assertRedirect();

        $this->assertDatabaseHas('sale_returns', ['sale_id' => $saleItem->sale_id, 'user_id' => $cashier->id, 'total' => 12000]);
        $this->assertDatabaseHas('sale_return_items', ['sale_item_id' => $saleItem->id, 'quantity' => 1, 'restock' => 1]);
        $this->assertSame(3, $product->fresh()->stock);
        $this->actingAs($cashier)->get(route('sale-returns.index'))
            ->assertOk()
            ->assertSee('Retur Penjualan')
            ->assertSee('Kemasan rusak');
    }
}
