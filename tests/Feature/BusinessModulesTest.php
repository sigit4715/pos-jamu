<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_shift_tracks_cash_sale_and_can_close(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'SHIFT-1', 'name' => 'Jamu Shift', 'price' => 12000, 'stock' => 5, 'minimum_stock' => 2, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($cashier)->post(route('shifts.open'), ['opening_cash' => 100000])->assertRedirect();
        $shift = CashierShift::firstOrFail();
        $this->actingAs($cashier)->post(route('sales.store'), ['payment_method' => 'cash', 'paid_amount' => 12000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertRedirect();
        $this->assertDatabaseHas('sales', ['shift_id' => $shift->id]);

        $this->actingAs($cashier)->post(route('shifts.close'), ['closing_cash' => 112000])->assertRedirect();
        $this->assertDatabaseHas('cashier_shifts', ['id' => $shift->id, 'status' => 'closed', 'difference' => 0]);
    }

    public function test_cashier_can_record_non_sale_stock_outflow(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'OUT-1', 'name' => 'Jamu Rusak', 'price' => 10000, 'stock' => 5, 'minimum_stock' => 2, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($cashier)->post(route('outflows.store'), ['reason_type' => 'rusak', 'notes' => 'Botol pecah', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertRedirect();

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_outflows', ['reason_type' => 'rusak', 'total_qty' => 2]);
        $this->assertDatabaseHas('stock_logs', ['product_id' => $product->id, 'type' => 'outflow', 'quantity_change' => -2]);
    }

    public function test_member_can_be_created_and_used_in_sale(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'MEM-1', 'name' => 'Jamu Member', 'price' => 20000, 'stock' => 5, 'minimum_stock' => 2, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($cashier)->post(route('customers.store'), ['name' => 'Pelanggan Uji', 'phone' => '08120000111', 'is_active' => 1])->assertRedirect();
        $customer = Customer::firstOrFail();
        $this->actingAs($cashier)->post(route('sales.store'), ['customer_id' => $customer->id, 'payment_method' => 'cash', 'paid_amount' => 20000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertRedirect();

        $this->assertDatabaseHas('sales', ['customer_id' => $customer->id, 'customer_name' => 'Pelanggan Uji']);
        $this->assertSame(2, $customer->fresh()->points);
    }

    public function test_purchase_records_batch_and_supplier_debt_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::create(['name' => 'Supplier Kredit', 'is_active' => true]);
        $product = Product::create(['code' => 'BATCH-1', 'name' => 'Jamu Batch', 'price' => 15000, 'stock' => 0, 'minimum_stock' => 2, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($admin)->post(route('purchases.store'), ['supplier_id' => $supplier->id, 'payment_status' => 'credit', 'due_date' => today()->addDays(14)->toDateString(), 'paid_amount' => 0, 'items' => [['product_id' => $product->id, 'quantity' => 4, 'price' => 7000, 'batch_number' => 'B-2026-01', 'expires_at' => today()->addDays(20)->toDateString()]]])->assertRedirect();
        $purchase = Purchase::firstOrFail();

        $this->assertDatabaseHas('product_batches', ['product_id' => $product->id, 'batch_number' => 'B-2026-01', 'remaining_quantity' => 4]);
        $this->assertSame(28000.0, (float) $purchase->outstanding);
        $this->actingAs($admin)->post(route('supplier-debts.pay', $purchase), ['amount' => 10000, 'method' => 'transfer'])->assertRedirect();
        $this->assertDatabaseHas('supplier_payments', ['purchase_id' => $purchase->id, 'amount' => 10000]);
        $this->assertSame('partial', $purchase->fresh()->payment_status);
    }

    public function test_promotion_cash_book_and_export_are_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create(['code' => 'PROMO-1', 'name' => 'Jamu Promo', 'price' => 10000, 'stock' => 5, 'minimum_stock' => 2, 'unit' => 'botol', 'is_active' => true]);
        $this->actingAs($admin)->post(route('promotions.store'), ['product_id' => $product->id, 'name' => 'Potongan Uji', 'type' => 'fixed', 'value' => 1000, 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('promotions', ['product_id' => $product->id, 'value' => 1000]);

        $this->actingAs($cashier)->post(route('cash.store'), ['type' => 'expense', 'category' => 'Transport', 'amount' => 5000, 'description' => 'Antar barang'])->assertRedirect();
        $this->actingAs($cashier)->post(route('sales.store'), ['payment_method' => 'cash', 'paid_amount' => 9000, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertRedirect();
        $this->assertDatabaseHas('sales', ['total' => 9000]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'sale.created']);
        $this->actingAs($admin)->get(route('reports.sales.export'))->assertDownload();
    }

    public function test_dashboard_renders_operational_summary_for_admin_and_cashier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        Product::create(['code' => 'DASH-1', 'name' => 'Jamu Dashboard', 'price' => 10000, 'buy_price' => 5000, 'stock' => 2, 'minimum_stock' => 3, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan toko hari ini')
            ->assertSee('Ringkasan Keuangan')
            ->assertSee('Rekomendasi Pembelian')
            ->assertSee('Peringatan stok menipis');

        $this->actingAs($cashier)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Status shift')
            ->assertDontSee('Hutang supplier');
    }

    public function test_admin_can_record_owner_capital_and_view_cash_flow_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('owner-capital.store'), [
            'type' => 'capital_in',
            'amount' => 500000,
            'description' => 'Modal awal toko',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('owner_capital_transactions', [
            'type' => 'capital_in',
            'amount' => 500000,
        ]);
        $this->actingAs($admin)->get(route('owner-capital.index'))->assertOk()->assertSee('Modal awal toko');
        $this->actingAs($admin)->get(route('reports.cash-flow'))->assertOk()->assertSee('Saldo kas akhir');
    }

    public function test_stock_opname_uses_server_side_pagination_and_links_to_stock_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstProduct = null;

        foreach (range(1, 21) as $number) {
            $product = Product::create([
                'code' => 'OPN-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'name' => 'Produk Opname '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'price' => 10000,
                'stock' => $number,
                'minimum_stock' => 2,
                'unit' => 'botol',
                'is_active' => true,
            ]);
            $firstProduct ??= $product;
        }

        $this->actingAs($admin)->get(route('opname.index', ['per_page' => 20]))
            ->assertOk()
            ->assertSee('Menampilkan 1-20 dari 21 barang.')
            ->assertSee('Produk Opname 01')
            ->assertDontSee('Produk Opname 21')
            ->assertSee(route('stock-card.index', ['product_id' => $firstProduct->id]), false);
    }
}
