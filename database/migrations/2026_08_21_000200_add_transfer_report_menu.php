<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('code', 'reports.view')->value('id');
        $now = now();
        DB::table('menu_items')->upsert([[ 'code' => 'laporan_transfer', 'name' => 'Transfer', 'section' => 'Keuangan', 'icon' => 'arrows', 'route_name' => 'reports.transfers', 'route_pattern' => 'reports.transfers', 'permission_id' => $permissionId, 'sort_order' => 70, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now ]], ['code'], ['name', 'section', 'icon', 'route_name', 'route_pattern', 'permission_id', 'sort_order', 'is_active', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('code', 'laporan_transfer')->delete();
    }
};
