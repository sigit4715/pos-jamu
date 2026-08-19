<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('purchases')->where('payment_status', 'paid')->where('paid_amount', 0)->update(['paid_amount' => DB::raw('total')]);
    }

    public function down(): void
    {
        // Histori pembayaran tidak dihapus saat rollback.
    }
};
