<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('member_code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['name', 'phone']);
        });

        Schema::create('stock_outflows', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('reason_type');
            $table->unsignedInteger('total_qty')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_outflow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_outflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete()->after('cashier_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete()->after('shift_id');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('type', [
                'initial', 'adjustment', 'sale', 'purchase',
                'purchase_return', 'sale_return', 'opname', 'outflow',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['shift_id']);
            $table->dropColumn(['customer_id', 'shift_id']);
        });
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->enum('type', [
                'initial', 'adjustment', 'sale', 'purchase',
                'purchase_return', 'sale_return', 'opname',
            ])->change();
        });
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('stock_outflow_items');
        Schema::dropIfExists('stock_outflows');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('cashier_shifts');
    }
};
