<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location_scope')->default('assigned');
            $table->string('location_type')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('access_role_id')->constrained('access_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['access_role_id', 'permission_id']);
        });

        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed');
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('section');
            $table->string('icon')->default('dashboard');
            $table->string('route_name');
            $table->string('route_pattern')->nullable();
            $table->foreignId('permission_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('access_role_id')->nullable()->after('role')->constrained('access_roles')->nullOnDelete();
            $table->boolean('is_system_owner')->default(false)->after('access_role_id');
        });

        // On migrate --pretend Laravel does not create the preceding tables, so data seeding is intentionally skipped.
        if (! Schema::hasTable('access_roles')) {
            return;
        }

        $now = now();
        $permissions = [
            ['code' => 'dashboard.view', 'name' => 'Lihat dashboard', 'group_name' => 'Dashboard', 'description' => 'Melihat ringkasan operasional'],
            ['code' => 'dashboard.view_all', 'name' => 'Dashboard semua transaksi', 'group_name' => 'Dashboard', 'description' => 'Melihat ringkasan seluruh aktivitas lokasi'],
            ['code' => 'sales.create', 'name' => 'Buat penjualan', 'group_name' => 'Penjualan', 'description' => 'Memproses transaksi kasir'],
            ['code' => 'sales.view', 'name' => 'Lihat riwayat penjualan', 'group_name' => 'Penjualan', 'description' => 'Melihat riwayat penjualan sendiri'],
            ['code' => 'sales.view_all', 'name' => 'Lihat semua penjualan', 'group_name' => 'Penjualan', 'description' => 'Melihat semua penjualan dalam lokasi aktif'],
            ['code' => 'shifts.manage', 'name' => 'Kelola shift', 'group_name' => 'Kasir', 'description' => 'Buka dan tutup shift'],
            ['code' => 'shifts.view_all', 'name' => 'Lihat semua shift', 'group_name' => 'Kasir', 'description' => 'Melihat shift semua pengguna'],
            ['code' => 'customers.manage', 'name' => 'Kelola pelanggan', 'group_name' => 'Penjualan', 'description' => 'Tambah dan ubah data pelanggan'],
            ['code' => 'purchases.manage', 'name' => 'Kelola pembelian', 'group_name' => 'Persediaan', 'description' => 'Input dan melihat pembelian barang'],
            ['code' => 'purchase_returns.manage', 'name' => 'Retur pembelian', 'group_name' => 'Persediaan', 'description' => 'Memproses retur ke supplier'],
            ['code' => 'sales_returns.manage', 'name' => 'Retur penjualan', 'group_name' => 'Persediaan', 'description' => 'Memproses retur pelanggan'],
            ['code' => 'stock.view', 'name' => 'Lihat kartu stok', 'group_name' => 'Persediaan', 'description' => 'Melihat mutasi dan stok barang'],
            ['code' => 'stock.opname.view', 'name' => 'Lihat stock opname', 'group_name' => 'Persediaan', 'description' => 'Melihat data stock opname'],
            ['code' => 'stock.opname.adjust', 'name' => 'Simpan stock opname', 'group_name' => 'Persediaan', 'description' => 'Mengubah stok berdasarkan opname'],
            ['code' => 'stock.transfer', 'name' => 'Transfer stok gudang', 'group_name' => 'Persediaan', 'description' => 'Transfer stok dari gudang ke toko'],
            ['code' => 'batches.view', 'name' => 'Lihat batch dan kedaluwarsa', 'group_name' => 'Persediaan', 'description' => 'Melihat batch serta tanggal kedaluwarsa'],
            ['code' => 'cash.manage', 'name' => 'Kelola kas', 'group_name' => 'Keuangan', 'description' => 'Input kas masuk dan kas keluar'],
            ['code' => 'outflows.manage', 'name' => 'Kelola pengeluaran barang', 'group_name' => 'Persediaan', 'description' => 'Mencatat barang keluar non-penjualan'],
            ['code' => 'products.manage', 'name' => 'Kelola master barang', 'group_name' => 'Master', 'description' => 'Tambah dan ubah barang serta satuan jual'],
            ['code' => 'master.manage', 'name' => 'Kelola master data', 'group_name' => 'Master', 'description' => 'Kelola kategori, satuan, merek, dan supplier'],
            ['code' => 'barcodes.view', 'name' => 'Cetak barcode', 'group_name' => 'Master', 'description' => 'Membuka label dan barcode barang'],
            ['code' => 'promotions.manage', 'name' => 'Kelola promo', 'group_name' => 'Master', 'description' => 'Mengatur promo dan harga'],
            ['code' => 'supplier_debts.manage', 'name' => 'Kelola hutang supplier', 'group_name' => 'Keuangan', 'description' => 'Melihat dan membayar hutang supplier'],
            ['code' => 'owner_capital.manage', 'name' => 'Kelola modal pemilik', 'group_name' => 'Keuangan', 'description' => 'Mencatat modal dan penarikan pemilik'],
            ['code' => 'audit.view', 'name' => 'Lihat audit aktivitas', 'group_name' => 'Administrasi', 'description' => 'Melihat histori aktivitas sistem'],
            ['code' => 'users.manage', 'name' => 'Kelola akun pengguna', 'group_name' => 'Administrasi', 'description' => 'Tambah dan ubah akun pengguna'],
            ['code' => 'stores.manage', 'name' => 'Kelola toko dan gudang', 'group_name' => 'Administrasi', 'description' => 'Tambah dan ubah lokasi operasional'],
            ['code' => 'stores.switch', 'name' => 'Pilih lokasi aktif', 'group_name' => 'Administrasi', 'description' => 'Berpindah tampilan antar lokasi'],
            ['code' => 'settings.manage', 'name' => 'Kelola pengaturan toko', 'group_name' => 'Administrasi', 'description' => 'Mengubah identitas dan pengaturan toko'],
            ['code' => 'roles.manage', 'name' => 'Kelola role dan izin', 'group_name' => 'Administrasi', 'description' => 'Mengatur hak akses per role dan pengguna'],
            ['code' => 'menus.manage', 'name' => 'Kelola menu sidebar', 'group_name' => 'Administrasi', 'description' => 'Mengaktifkan dan mengurutkan menu'],
            ['code' => 'reports.view', 'name' => 'Lihat dan ekspor laporan', 'group_name' => 'Laporan', 'description' => 'Membuka laporan dan file ekspor'],
        ];
        foreach ($permissions as &$permission) { $permission['created_at'] = $now; $permission['updated_at'] = $now; }
        DB::table('permissions')->upsert($permissions, ['code'], ['name', 'group_name', 'description', 'updated_at']);

        $roles = [
            ['code' => 'admin', 'name' => 'Administrator', 'description' => 'Mengelola seluruh lokasi dan konfigurasi operasional.', 'location_scope' => 'all', 'location_type' => null, 'is_system' => true],
            ['code' => 'kasir', 'name' => 'Kasir', 'description' => 'Melayani transaksi dan administrasi kasir di toko tugasnya.', 'location_scope' => 'assigned', 'location_type' => 'store', 'is_system' => true],
            ['code' => 'gudang', 'name' => 'Petugas Gudang', 'description' => 'Mengelola stok, pembelian, dan transfer pada gudang tugasnya.', 'location_scope' => 'assigned', 'location_type' => 'warehouse', 'is_system' => true],
        ];
        foreach ($roles as &$role) { $role['created_at'] = $now; $role['updated_at'] = $now; }
        DB::table('access_roles')->upsert($roles, ['code'], ['name', 'description', 'location_scope', 'location_type', 'is_system', 'updated_at']);

        $permissionIds = DB::table('permissions')->pluck('id', 'code');
        $roleIds = DB::table('access_roles')->pluck('id', 'code');
        $allPermissions = $permissionIds->values()->all();
        $rolePermissions = [
            'admin' => $allPermissions,
            'kasir' => ['dashboard.view', 'sales.create', 'sales.view', 'shifts.manage', 'customers.manage', 'purchases.manage', 'sales_returns.manage', 'stock.view', 'stock.opname.view', 'batches.view', 'cash.manage', 'outflows.manage'],
            'gudang' => ['dashboard.view', 'sales.create', 'sales.view', 'shifts.manage', 'customers.manage', 'purchases.manage', 'purchase_returns.manage', 'sales_returns.manage', 'stock.view', 'stock.opname.view', 'stock.opname.adjust', 'stock.transfer', 'batches.view', 'cash.manage', 'outflows.manage'],
        ];
        foreach ($rolePermissions as $roleCode => $codes) {
            $rows = collect($codes)->map(fn ($code) => ['access_role_id' => $roleIds[$roleCode], 'permission_id' => is_numeric($code) ? $code : $permissionIds[$code]])->all();
            DB::table('role_permission')->insertOrIgnore($rows);
        }

        $menus = [
            ['code' => 'dashboard', 'name' => 'Dashboard', 'section' => 'Menu Utama', 'icon' => 'dashboard', 'route_name' => 'dashboard', 'route_pattern' => 'dashboard', 'permission_code' => 'dashboard.view', 'sort_order' => 10],
            ['code' => 'kasir', 'name' => 'Kasir', 'section' => 'Menu Utama', 'icon' => 'cart', 'route_name' => 'sales.create', 'route_pattern' => 'sales.create', 'permission_code' => 'sales.create', 'sort_order' => 20],
            ['code' => 'penjualan', 'name' => 'Penjualan', 'section' => 'Menu Utama', 'icon' => 'receipt', 'route_name' => 'sales.index', 'route_pattern' => 'sales.index', 'permission_code' => 'sales.view', 'sort_order' => 30],
            ['code' => 'shift', 'name' => 'Shift Kasir', 'section' => 'Menu Utama', 'icon' => 'clock', 'route_name' => 'shifts.index', 'route_pattern' => 'shifts.*', 'permission_code' => 'shifts.manage', 'sort_order' => 40],
            ['code' => 'pelanggan', 'name' => 'Pelanggan / Member', 'section' => 'Menu Utama', 'icon' => 'users', 'route_name' => 'customers.index', 'route_pattern' => 'customers.*', 'permission_code' => 'customers.manage', 'sort_order' => 50],
            ['code' => 'pembelian', 'name' => 'Pembelian / Barang Masuk', 'section' => 'Persediaan', 'icon' => 'package-plus', 'route_name' => 'purchases.index', 'route_pattern' => 'purchases.*', 'permission_code' => 'purchases.manage', 'sort_order' => 10],
            ['code' => 'pengeluaran_barang', 'name' => 'Pengeluaran Barang', 'section' => 'Persediaan', 'icon' => 'package-minus', 'route_name' => 'outflows.index', 'route_pattern' => 'outflows.*', 'permission_code' => 'outflows.manage', 'sort_order' => 20],
            ['code' => 'kartu_stok', 'name' => 'Kartu Stok', 'section' => 'Persediaan', 'icon' => 'boxes', 'route_name' => 'stock-card.index', 'route_pattern' => 'stock-card.*', 'permission_code' => 'stock.view', 'sort_order' => 30],
            ['code' => 'batch', 'name' => 'Batch & Kedaluwarsa', 'section' => 'Persediaan', 'icon' => 'calendar', 'route_name' => 'batches.index', 'route_pattern' => 'batches.*', 'permission_code' => 'batches.view', 'sort_order' => 40],
            ['code' => 'kas', 'name' => 'Kas & Pengeluaran', 'section' => 'Persediaan', 'icon' => 'wallet', 'route_name' => 'cash.index', 'route_pattern' => 'cash.*', 'permission_code' => 'cash.manage', 'sort_order' => 50],
            ['code' => 'opname', 'name' => 'Stock Opname', 'section' => 'Persediaan', 'icon' => 'clipboard', 'route_name' => 'opname.index', 'route_pattern' => 'opname.*', 'permission_code' => 'stock.opname.view', 'sort_order' => 60],
            ['code' => 'retur_penjualan', 'name' => 'Retur Penjualan', 'section' => 'Persediaan', 'icon' => 'undo', 'route_name' => 'sale-returns.index', 'route_pattern' => 'sale-returns.*', 'permission_code' => 'sales_returns.manage', 'sort_order' => 70],
            ['code' => 'transfer_stok', 'name' => 'Transfer ke Toko', 'section' => 'Persediaan', 'icon' => 'arrows', 'route_name' => 'stock-transfers.index', 'route_pattern' => 'stock-transfers.*', 'permission_code' => 'stock.transfer', 'sort_order' => 80],
            ['code' => 'retur_supplier', 'name' => 'Retur Supplier', 'section' => 'Persediaan', 'icon' => 'undo', 'route_name' => 'purchase-returns.index', 'route_pattern' => 'purchase-returns.*', 'permission_code' => 'purchase_returns.manage', 'sort_order' => 90],
            ['code' => 'master_barang', 'name' => 'Master Barang', 'section' => 'Administrasi', 'icon' => 'cube', 'route_name' => 'products.index', 'route_pattern' => 'products.*', 'permission_code' => 'products.manage', 'sort_order' => 10],
            ['code' => 'master_data', 'name' => 'Master Data', 'section' => 'Administrasi', 'icon' => 'database', 'route_name' => 'master.index', 'route_pattern' => 'master.*', 'permission_code' => 'master.manage', 'sort_order' => 20],
            ['code' => 'barcode', 'name' => 'Barcode & Label', 'section' => 'Administrasi', 'icon' => 'barcode', 'route_name' => 'barcodes.index', 'route_pattern' => 'barcodes.*', 'permission_code' => 'barcodes.view', 'sort_order' => 30],
            ['code' => 'promo', 'name' => 'Promo & Harga', 'section' => 'Administrasi', 'icon' => 'tag', 'route_name' => 'promotions.index', 'route_pattern' => 'promotions.*', 'permission_code' => 'promotions.manage', 'sort_order' => 40],
            ['code' => 'hutang_supplier', 'name' => 'Hutang Supplier', 'section' => 'Administrasi', 'icon' => 'coins', 'route_name' => 'supplier-debts.index', 'route_pattern' => 'supplier-debts.*', 'permission_code' => 'supplier_debts.manage', 'sort_order' => 50],
            ['code' => 'modal_pemilik', 'name' => 'Modal Pemilik', 'section' => 'Administrasi', 'icon' => 'landmark', 'route_name' => 'owner-capital.index', 'route_pattern' => 'owner-capital.*', 'permission_code' => 'owner_capital.manage', 'sort_order' => 60],
            ['code' => 'audit', 'name' => 'Audit Aktivitas', 'section' => 'Administrasi', 'icon' => 'search', 'route_name' => 'audit.index', 'route_pattern' => 'audit.*', 'permission_code' => 'audit.view', 'sort_order' => 70],
            ['code' => 'akun', 'name' => 'Akun Pengguna', 'section' => 'Administrasi', 'icon' => 'user', 'route_name' => 'users.index', 'route_pattern' => 'users.*', 'permission_code' => 'users.manage', 'sort_order' => 80],
            ['code' => 'role_izin', 'name' => 'Role & Hak Akses', 'section' => 'Administrasi', 'icon' => 'settings', 'route_name' => 'roles.index', 'route_pattern' => 'roles.*', 'permission_code' => 'roles.manage', 'sort_order' => 90],
            ['code' => 'menu_sidebar', 'name' => 'Pengaturan Menu', 'section' => 'Administrasi', 'icon' => 'menu', 'route_name' => 'menus.index', 'route_pattern' => 'menus.*', 'permission_code' => 'menus.manage', 'sort_order' => 100],
            ['code' => 'toko_gudang', 'name' => 'Manajemen Toko', 'section' => 'Administrasi', 'icon' => 'landmark', 'route_name' => 'stores.index', 'route_pattern' => 'stores.*', 'permission_code' => 'stores.manage', 'sort_order' => 110],
            ['code' => 'pengaturan', 'name' => 'Pengaturan Toko', 'section' => 'Administrasi', 'icon' => 'settings', 'route_name' => 'settings.index', 'route_pattern' => 'settings.*', 'permission_code' => 'settings.manage', 'sort_order' => 120],
            ['code' => 'laporan_penjualan', 'name' => 'Penjualan', 'section' => 'Laporan', 'icon' => 'chart', 'route_name' => 'reports.index', 'route_pattern' => 'reports.index', 'permission_code' => 'reports.view', 'sort_order' => 10],
            ['code' => 'laporan_pembelian', 'name' => 'Pembelian', 'section' => 'Laporan', 'icon' => 'chart', 'route_name' => 'reports.purchases', 'route_pattern' => 'reports.purchases', 'permission_code' => 'reports.view', 'sort_order' => 20],
            ['code' => 'laporan_stok', 'name' => 'Stok', 'section' => 'Laporan', 'icon' => 'boxes', 'route_name' => 'reports.stock', 'route_pattern' => 'reports.stock', 'permission_code' => 'reports.view', 'sort_order' => 30],
            ['code' => 'laporan_laba', 'name' => 'Keuntungan', 'section' => 'Laporan', 'icon' => 'trending', 'route_name' => 'reports.profit', 'route_pattern' => 'reports.profit', 'permission_code' => 'reports.view', 'sort_order' => 40],
            ['code' => 'laporan_retur', 'name' => 'Retur', 'section' => 'Laporan', 'icon' => 'undo', 'route_name' => 'reports.returns', 'route_pattern' => 'reports.returns', 'permission_code' => 'reports.view', 'sort_order' => 50],
            ['code' => 'laporan_kas', 'name' => 'Arus Kas', 'section' => 'Laporan', 'icon' => 'arrows', 'route_name' => 'reports.cash-flow', 'route_pattern' => 'reports.cash-flow', 'permission_code' => 'reports.view', 'sort_order' => 60],
        ];
        foreach ($menus as &$menu) {
            $menu['permission_id'] = $permissionIds[$menu['permission_code']];
            unset($menu['permission_code']);
            $menu['is_active'] = true;
            $menu['is_system'] = true;
            $menu['created_at'] = $now;
            $menu['updated_at'] = $now;
        }
        DB::table('menu_items')->upsert($menus, ['code'], ['name', 'section', 'icon', 'route_name', 'route_pattern', 'permission_id', 'sort_order', 'is_active', 'updated_at']);

        DB::table('users')->orderBy('id')->select('id', 'role')->each(function (object $user) use ($roleIds) {
            DB::table('users')->where('id', $user->id)->update(['access_role_id' => $roleIds[$user->role] ?? $roleIds['kasir']]);
        });
        $ownerId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
        if ($ownerId) {
            DB::table('users')->where('id', $ownerId)->update(['is_system_owner' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_role_id');
            $table->dropColumn('is_system_owner');
        });
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('user_permission_overrides');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('access_roles');
    }
};
