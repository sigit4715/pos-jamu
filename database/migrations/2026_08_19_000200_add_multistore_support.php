<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $mainStoreId = DB::table('stores')->insertGetId([
            'code' => 'TOKO-UTAMA',
            'name' => 'Toko Utama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            'users',
            'products',
            'sales',
            'purchases',
            'purchase_returns',
            'sale_returns',
            'stock_opnames',
            'stock_outflows',
            'stock_logs',
            'cashier_shifts',
            'customers',
            'cash_transactions',
            'supplier_payments',
            'product_batches',
            'promotions',
            'activity_logs',
            'owner_capital_transactions',
            'store_settings',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('id')->constrained('stores')->restrictOnDelete();
            });
        }

        foreach ([
            'users',
            'products',
            'sales',
            'purchases',
            'purchase_returns',
            'sale_returns',
            'stock_opnames',
            'stock_outflows',
            'stock_logs',
            'cashier_shifts',
            'customers',
            'cash_transactions',
            'supplier_payments',
            'product_batches',
            'promotions',
            'activity_logs',
            'owner_capital_transactions',
            'store_settings',
        ] as $tableName) {
            DB::table($tableName)->whereNull('store_id')->update(['store_id' => $mainStoreId]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropUnique(['barcode']);
            $table->unique(['store_id', 'code'], 'products_store_code_unique');
            $table->unique(['store_id', 'barcode'], 'products_store_barcode_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['member_code']);
            $table->unique(['store_id', 'member_code'], 'customers_store_member_code_unique');
        });

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['store_id', 'key'], 'store_settings_store_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropUnique('store_settings_store_key_unique');
            $table->unique('key');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_store_member_code_unique');
            $table->unique('member_code');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_store_code_unique');
            $table->dropUnique('products_store_barcode_unique');
            $table->unique('code');
            $table->unique('barcode');
        });

        foreach ([
            'users',
            'products',
            'sales',
            'purchases',
            'purchase_returns',
            'sale_returns',
            'stock_opnames',
            'stock_outflows',
            'stock_logs',
            'cashier_shifts',
            'customers',
            'cash_transactions',
            'supplier_payments',
            'product_batches',
            'promotions',
            'activity_logs',
            'owner_capital_transactions',
            'store_settings',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('store_id');
            });
        }

        Schema::dropIfExists('stores');
    }
};
