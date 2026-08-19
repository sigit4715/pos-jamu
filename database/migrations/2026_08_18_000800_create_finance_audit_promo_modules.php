<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('remaining_quantity')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['product_id', 'expires_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_status')->default('paid')->after('total');
            $table->date('due_date')->nullable()->after('payment_status');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('due_date');
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at');
            $table->string('method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type');
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['type', 'occurred_at']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('value', 15, 2);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('supplier_payments');
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'due_date', 'paid_amount']);
        });
        Schema::dropIfExists('product_batches');
    }
};
