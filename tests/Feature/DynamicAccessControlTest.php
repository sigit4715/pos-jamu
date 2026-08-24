<?php

namespace Tests\Feature;

use App\Models\AccessRole;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
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

    public function test_product_edit_form_compiles_with_dynamic_packaging_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create(['code' => 'FORM-01', 'name' => 'Jamu Form', 'price' => 10000, 'stock' => 2, 'unit' => 'pcs', 'is_active' => true]);

        $this->actingAs($admin)->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Kelola Satuan')
            ->assertSee(route('master.index').'#satuan', false);
    }

    public function test_product_form_can_load_all_units_stored_in_master_data_without_reloading(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('master.store', 'units'), [
            'name' => 'Dus',
            'symbol' => 'dus',
        ])->assertRedirect();

        $unit = Unit::where('name', 'Dus')->firstOrFail();
        $olderUnit = Unit::create([
            'name' => 'Karton Lama',
            'symbol' => 'krt',
            'is_active' => false,
        ]);

        $this->actingAs($admin)->get(route('products.create'))
            ->assertOk()
            ->assertSee('Dus')
            ->assertSee('Karton Lama');

        $this->actingAs($admin)->getJson(route('products.units.index'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $unit->id,
                'name' => 'Dus',
                'symbol' => 'dus',
            ])
            ->assertJsonFragment([
                'id' => $olderUnit->id,
                'name' => 'Karton Lama',
                'symbol' => 'krt',
            ]);
    }

    public function test_product_form_can_save_a_unit_with_the_quick_add_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson(route('products.units.store'), [
            'name' => 'Pouch',
            'symbol' => 'pch',
        ])->assertCreated()
            ->assertJsonFragment(['name' => 'Pouch', 'symbol' => 'pch']);

        $this->assertDatabaseHas('units', ['name' => 'Pouch', 'symbol' => 'pch', 'is_active' => true]);
    }

    public function test_only_admin_can_manage_login_accounts_even_with_a_permission_override(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $permission = Permission::where('code', 'users.manage')->firstOrFail();
        UserPermissionOverride::create(['user_id' => $cashier->id, 'permission_id' => $permission->id, 'is_allowed' => true]);

        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.create'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_admin_can_open_clickable_location_activity_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Aktivitas lokasi hari ini')
            ->assertSee('Detail aktivitas');
    }

    public function test_admin_can_preview_cashier_menu_without_logging_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.menu-preview.update'), ['role' => 'kasir'])
            ->assertRedirect()
            ->assertSessionHas('menu_preview_role', 'kasir');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pratinjau Kasir')
            ->assertDontSee('Manajemen Toko');
    }
}
