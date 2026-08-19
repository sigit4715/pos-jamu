<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('type')->default('store')->after('name');
            $table->index(['type', 'is_active']);
        });

        Schema::create('product_packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('conversion_quantity');
            $table->decimal('price', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'name']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_packaging_id')->nullable()->after('product_id')->constrained('product_packagings')->nullOnDelete();
            $table->string('unit_name')->default('pcs')->after('product_name');
            $table->unsignedInteger('conversion_quantity')->default(1)->after('unit_name');
            $table->unsignedInteger('base_quantity')->default(0)->after('quantity');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_packaging_id')->nullable()->after('product_id')->constrained('product_packagings')->nullOnDelete();
            $table->string('unit_name')->default('pcs')->after('product_name');
            $table->unsignedInteger('conversion_quantity')->default(1)->after('unit_name');
            $table->unsignedInteger('base_quantity')->default(0)->after('quantity');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->unsignedInteger('transaction_quantity')->nullable()->after('quantity_change');
            $table->string('unit_name')->nullable()->after('transaction_quantity');
            $table->unsignedInteger('conversion_quantity')->default(1)->after('unit_name');
            $table->enum('type', [
                'initial', 'adjustment', 'sale', 'purchase', 'purchase_return',
                'sale_return', 'opname', 'outflow', 'transfer_in', 'transfer_out',
            ])->change();
        });

        DB::table('sale_items')->where('base_quantity', 0)->update([
            'base_quantity' => DB::raw('quantity'),
            'conversion_quantity' => 1,
        ]);
        DB::table('purchase_items')->where('base_quantity', 0)->update([
            'base_quantity' => DB::raw('quantity'),
            'conversion_quantity' => 1,
        ]);
        DB::table('stock_logs')->whereNull('transaction_quantity')->update([
            'transaction_quantity' => DB::raw('ABS(quantity_change)'),
            'conversion_quantity' => 1,
        ]);

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('source_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('destination_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('transferred_at');
            $table->timestamps();
            $table->index(['source_store_id', 'transferred_at']);
            $table->index(['destination_store_id', 'transferred_at']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('destination_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_packaging_id')->nullable()->constrained('product_packagings')->nullOnDelete();
            $table->string('product_name');
            $table->string('unit_name');
            $table->unsignedInteger('conversion_quantity')->default(1);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('base_quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('type', ['initial', 'adjustment', 'sale', 'purchase', 'purchase_return', 'sale_return', 'opname', 'outflow'])->change();
            $table->dropColumn(['transaction_quantity', 'unit_name', 'conversion_quantity']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_packaging_id');
            $table->dropColumn(['unit_name', 'conversion_quantity', 'base_quantity']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_packaging_id');
            $table->dropColumn(['unit_name', 'conversion_quantity', 'base_quantity']);
        });

        Schema::dropIfExists('product_packagings');
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropColumn('type');
        });
    }
};
