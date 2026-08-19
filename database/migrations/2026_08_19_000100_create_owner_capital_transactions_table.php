<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('owner_capital_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type'); // capital_in or capital_withdrawal
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_capital_transactions');
    }
};
