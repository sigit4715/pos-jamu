<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiStoreIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_and_admin_only_see_the_active_store_data(): void
    {
        $mainStore = Store::query()->firstOrFail();
        $branchStore = Store::create(['code' => 'CABANG-01', 'name' => 'Toko Cabang', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $mainCashier = User::factory()->create(['role' => 'kasir', 'store_id' => $mainStore->id]);
        $branchCashier = User::factory()->create(['role' => 'kasir', 'store_id' => $branchStore->id]);
        $mainProduct = Product::create(['store_id' => $mainStore->id, 'code' => 'MAIN-01', 'name' => 'Jamu Toko Utama', 'price' => 10000, 'stock' => 5, 'unit' => 'botol', 'is_active' => true]);
        $branchProduct = Product::create(['store_id' => $branchStore->id, 'code' => 'BRANCH-01', 'name' => 'Jamu Toko Cabang', 'price' => 15000, 'stock' => 7, 'unit' => 'botol', 'is_active' => true]);

        $this->actingAs($mainCashier)->get(route('sales.create'))
            ->assertOk()
            ->assertSee($mainProduct->name)
            ->assertDontSee($branchProduct->name);

        $this->actingAs($mainCashier)->post(route('sales.store'), [
            'idempotency_key' => '5e9f2200-c5c1-4a1f-a137-1e0349aa2004',
            'payment_method' => 'cash',
            'paid_amount' => 10000,
            'items' => [['product_id' => $mainProduct->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['store_id' => $mainStore->id, 'cashier_id' => $mainCashier->id]);
        $this->assertSame(4, $mainProduct->fresh()->stock);
        $this->assertSame(7, $branchProduct->fresh()->stock);

        $this->actingAs($branchCashier)->get(route('sales.index'))
            ->assertOk()
            ->assertDontSee(Sale::query()->firstOrFail()->invoice_number);

        $this->actingAs($admin)->post(route('stores.switch'), ['store_id' => $branchStore->id])->assertRedirect();
        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee($branchProduct->name)
            ->assertDontSee($mainProduct->name);
    }
}
