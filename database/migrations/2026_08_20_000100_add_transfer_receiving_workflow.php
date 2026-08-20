<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('status')->default('shipped')->after('notes');
            $table->dateTime('received_at')->nullable()->after('transferred_at');
            $table->foreignId('received_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->index(['destination_store_id', 'status']);
        });

        DB::table('stock_transfers')->update([
            'status' => 'received',
            'received_at' => DB::raw('transferred_at'),
        ]);

        $now = now();
        DB::table('permissions')->upsert([[
            'code' => 'stock.transfer.receive',
            'name' => 'Terima transfer stok',
            'group_name' => 'Persediaan',
            'description' => 'Mengonfirmasi barang transfer yang tiba di toko',
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['code'], ['name', 'group_name', 'description', 'updated_at']);

        $permissionId = DB::table('permissions')->where('code', 'stock.transfer.receive')->value('id');
        $roleIds = DB::table('access_roles')->whereIn('code', ['admin', 'kasir'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_permission')->insertOrIgnore(['access_role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        DB::table('menu_items')->whereIn('code', ['dashboard', 'kasir', 'penjualan', 'shift', 'pelanggan'])->update(['section' => 'Operasional']);
        DB::table('menu_items')->whereIn('code', ['pembelian', 'pengeluaran_barang', 'kartu_stok', 'batch', 'opname', 'retur_penjualan', 'transfer_stok', 'retur_supplier'])->update(['section' => 'Persediaan']);
        DB::table('menu_items')->whereIn('code', ['kas', 'hutang_supplier', 'modal_pemilik', 'laporan_penjualan', 'laporan_pembelian', 'laporan_stok', 'laporan_laba', 'laporan_retur', 'laporan_kas'])->update(['section' => 'Keuangan']);
        DB::table('menu_items')->whereIn('code', ['master_barang', 'master_data', 'barcode', 'promo', 'audit', 'akun', 'role_izin', 'menu_sidebar', 'toko_gudang', 'pengaturan'])->update(['section' => 'Pengaturan Lanjutan']);
        DB::table('menu_items')->upsert([[
            'code' => 'transfer_terima',
            'name' => 'Penerimaan Transfer',
            'section' => 'Persediaan',
            'icon' => 'package-plus',
            'route_name' => 'stock-transfers.incoming',
            'route_pattern' => 'stock-transfers.incoming',
            'permission_id' => $permissionId,
            'sort_order' => 85,
            'is_active' => true,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['code'], ['name', 'section', 'icon', 'route_name', 'route_pattern', 'permission_id', 'sort_order', 'is_active', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('code', 'transfer_terima')->delete();
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex(['destination_store_id', 'status']);
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn(['status', 'received_at']);
        });
    }
};
