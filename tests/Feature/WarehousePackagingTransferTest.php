<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\Sale;
use App\Models\StockLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePackagingTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_user_can_transfer_cartons_and_sell_in_carton_units(): void
    {
        $store = Store::query()->firstOrFail();
        $warehouse = Store::create([
            'code' => 'GDG-UTAMA',
            'name' => 'Gudang Utama',
            'type' => 'warehouse',
            'is_active' => true,
        ]);
        $warehouseUser = User::factory()->create(['role' => 'gudang', 'store_id' => $warehouse->id]);
        $product = Product::create([
            'store_id' => $warehouse->id,
            'code' => 'JMU-KRT-01',
            'name' => 'Jamu Beras Kencur',
            'price' => 8000,
            'buy_price' => 5000,
            'stock' => 48,
            'minimum_stock' => 12,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $carton = ProductPackaging::create([
            'product_id' => $product->id,
            'name' => 'karton',
            'conversion_quantity' => 24,
            'price' => 180000,
            'is_active' => true,
        ]);

        $this->actingAs($warehouseUser)->post(route('stock-transfers.store'), [
            'destination_store_id' => $store->id,
            'notes' => 'Kirim satu karton',
            'items' => [[
                'product_id' => $product->id,
                'product_packaging_id' => $carton->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect();

        $destinationProduct = Product::where('store_id', $store->id)->where('code', $product->code)->firstOrFail();
        $this->assertSame(24, $product->fresh()->stock);
        $this->assertSame(0, $destinationProduct->stock);
        $this->assertDatabaseHas('stock_transfers', ['status' => 'shipped']);
        $this->assertDatabaseHas('stock_transfer_items', [
            'source_product_id' => $product->id,
            'destination_product_id' => $destinationProduct->id,
            'unit_name' => 'karton',
            'quantity' => 1,
            'base_quantity' => 24,
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'store_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'transfer_out',
            'quantity_change' => -24,
            'transaction_quantity' => 1,
            'unit_name' => 'karton',
        ]);
        $this->assertDatabaseMissing('stock_logs', [
            'store_id' => $store->id,
            'product_id' => $destinationProduct->id,
            'type' => 'transfer_in',
            'quantity_change' => 24,
            'transaction_quantity' => 1,
            'unit_name' => 'karton',
        ]);

        $cashier = User::factory()->create(['role' => 'kasir', 'store_id' => $store->id]);
        $transfer = \App\Models\StockTransfer::firstOrFail();
        $this->actingAs($cashier)->post(route('stock-transfers.receive', $transfer))->assertRedirect();
        $this->assertSame(24, $destinationProduct->fresh()->stock);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'received', 'received_by' => $cashier->id]);
        $this->assertDatabaseHas('stock_logs', ['store_id' => $store->id, 'product_id' => $destinationProduct->id, 'type' => 'transfer_in', 'quantity_change' => 24]);

        $this->actingAs($warehouseUser)->post(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 180000,
            'items' => [[
                'product_id' => $product->id,
                'product_packaging_id' => $carton->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect();

        $sale = Sale::where('store_id', $warehouse->id)->firstOrFail();
        $this->assertSame(180000.0, (float) $sale->total);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_packaging_id' => $carton->id,
            'unit_name' => 'karton',
            'conversion_quantity' => 24,
            'quantity' => 1,
            'base_quantity' => 24,
            'subtotal' => 180000,
        ]);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(1, StockLog::where('store_id', $warehouse->id)->where('type', 'sale')->where('quantity_change', -24)->count());
    }

    public function test_cashier_cannot_open_the_warehouse_transfer_screen(): void
    {
        $store = Store::query()->firstOrFail();
        $cashier = User::factory()->create(['role' => 'kasir', 'store_id' => $store->id]);

        $this->actingAs($cashier)
            ->get(route('stock-transfers.index'))
            ->assertForbidden();
    }
}
