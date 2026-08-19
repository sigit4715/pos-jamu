<?php

namespace Tests\Feature;

use App\Models\AccessRole;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPermissionOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_role_is_mapped_to_dynamic_permissions_when_a_user_is_created(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->assertSame('kasir', $cashier->accessRole->code);
        $this->assertTrue($cashier->hasPermission('sales.create'));
        $this->assertFalse($cashier->hasPermission('products.manage'));
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('sales.create'))->assertOk();
    }

    public function test_permission_override_blocks_direct_url_access_even_when_the_menu_exists(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $permission = Permission::where('code', 'sales.create')->firstOrFail();
        UserPermissionOverride::create(['user_id' => $cashier->id, 'permission_id' => $permission->id, 'is_allowed' => false]);

        $this->actingAs($cashier)->get(route('sales.create'))->assertForbidden();
    }

    public function test_custom_role_can_be_created_with_only_the_permissions_it_needs(): void
    {
        $store = Store::query()->firstOrFail();
        $role = AccessRole::create(['code' => 'auditor_toko', 'name' => 'Auditor Toko', 'location_scope' => 'assigned', 'location_type' => 'store']);
        $role->permissions()->sync(Permission::whereIn('code', ['dashboard.view', 'stock.view'])->pluck('id'));
        $user = User::factory()->create(['role' => $role->code, 'access_role_id' => $role->id, 'store_id' => $store->id]);

        $this->assertTrue($user->hasPermission('stock.view'));
        $this->assertFalse($user->hasPermission('sales.create'));
        $this->actingAs($user)->get(route('stock-card.index'))->assertOk();
        $this->actingAs($user)->get(route('sales.create'))->assertForbidden();
    }

    public function test_inactive_menu_is_hidden_from_sidebar_without_deleting_the_registered_route(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $menu = MenuItem::where('code', 'pembelian')->firstOrFail();
        $menu->update(['is_active' => false]);

        $this->actingAs($cashier)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pembelian / Barang Masuk');
        $this->actingAs($cashier)->get(route('purchases.index'))->assertOk();
    }
}
