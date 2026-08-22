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

        $response = $this->actingAs($cashier)->post(route('sales.store'), ['idempotency_key' => '72ef5d34-9684-4bda-a540-1e0349aa1000', 'payment_method' => 'cash', 'paid_amount' => 25000, 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['cashier_id' => $cashier->id, 'total' => 20000]);
        $this->assertDatabaseHas('sale_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_logs', ['product_id' => $product->id, 'type' => 'sale', 'stock_after' => 3]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_retrying_the_same_idempotency_key_creates_only_one_sale_and_stock_log(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'JMU-IDEMPOTENT', 'name' => 'Jamu Anti Ganda', 'price' => 10000, 'stock' => 5, 'unit' => 'botol', 'is_active' => true]);
        $payload = [
            'idempotency_key' => 'd2719be3-3fcd-4f1e-9bdc-1e0349aa1001',
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $this->actingAs($cashier)->postJson(route('sales.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'saved');
        $this->actingAs($cashier)->postJson(route('sales.store'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'already_saved');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('stock_logs', 1);
        $this->assertDatabaseHas('sales', ['idempotency_key' => $payload['idempotency_key']]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_sale_rolls_back_when_combined_lines_exceed_product_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'JMU-GABUNG', 'name' => 'Jamu Stok Gabung', 'price' => 10000, 'stock' => 5, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($cashier)->postJson(route('sales.store'), [
            'idempotency_key' => 'b02b4b8a-6f90-4de5-84e9-1e0349aa1002',
            'payment_method' => 'cash',
            'paid_amount' => 60000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_logs', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_cashier_can_check_the_status_of_a_saved_idempotent_sale(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'JMU-STATUS', 'name' => 'Jamu Status', 'price' => 10000, 'stock' => 3, 'unit' => 'botol', 'is_active' => true]);
        $key = 'ea478db0-d0bd-4c1f-b05b-1e0349aa1003';

        $this->actingAs($cashier)->postJson(route('sales.store'), [
            'idempotency_key' => $key,
            'payment_method' => 'cash',
            'paid_amount' => 10000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->actingAs($cashier)->getJson(route('sales.status', ['idempotencyKey' => $key]))
            ->assertOk()
            ->assertJsonPath('status', 'saved')
            ->assertJsonPath('invoice_number', 'JMU-' . now()->format('Ymd') . '-00000001');
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
            'idempotency_key' => '487fd88b-0bbe-4483-8559-1e0349aa1004',
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
