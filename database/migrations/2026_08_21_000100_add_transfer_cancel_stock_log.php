<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('type', ['initial', 'adjustment', 'sale', 'purchase', 'purchase_return', 'sale_return', 'opname', 'outflow', 'transfer_out', 'transfer_in', 'transfer_cancel'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('type', ['initial', 'adjustment', 'sale', 'purchase', 'purchase_return', 'sale_return', 'opname', 'outflow', 'transfer_out', 'transfer_in'])->change();
        });
    }
};
