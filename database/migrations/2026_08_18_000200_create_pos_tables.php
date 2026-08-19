<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->text('description')->nullable(); $table->timestamps(); });
        Schema::create('products', function (Blueprint $table) { $table->id(); $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); $table->string('code')->unique(); $table->string('name'); $table->text('description')->nullable(); $table->decimal('price', 15, 2); $table->integer('stock')->default(0); $table->string('unit')->default('botol'); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('sales', function (Blueprint $table) { $table->id(); $table->string('invoice_number')->unique(); $table->foreignId('cashier_id')->constrained('users'); $table->string('customer_name')->nullable(); $table->decimal('subtotal', 15, 2); $table->decimal('discount', 15, 2)->default(0); $table->decimal('total', 15, 2); $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash'); $table->decimal('paid_amount', 15, 2); $table->decimal('change_amount', 15, 2)->default(0); $table->text('notes')->nullable(); $table->timestamps(); });
        Schema::create('sale_items', function (Blueprint $table) { $table->id(); $table->foreignId('sale_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained(); $table->string('product_name'); $table->decimal('price', 15, 2); $table->integer('quantity'); $table->decimal('subtotal', 15, 2); $table->timestamps(); });
        Schema::create('stock_logs', function (Blueprint $table) { $table->id(); $table->foreignId('product_id')->constrained(); $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); $table->enum('type', ['initial', 'adjustment', 'sale']); $table->integer('quantity_change'); $table->integer('stock_before'); $table->integer('stock_after'); $table->string('reference')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); });
    }
    public function down(): void { Schema::dropIfExists('stock_logs'); Schema::dropIfExists('sale_items'); Schema::dropIfExists('sales'); Schema::dropIfExists('products'); Schema::dropIfExists('categories'); }
};
