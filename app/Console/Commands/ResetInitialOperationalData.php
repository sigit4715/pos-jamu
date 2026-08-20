<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetInitialOperationalData extends Command
{
    protected $signature = 'pos:reset-initial-data {--force : Jalankan penghapusan data operasional setelah backup dibuat}';

    protected $description = 'Backup lalu mulai ulang data operasional dengan Toko A, Toko B, dan Gudang';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Perintah ini menghapus data operasional. Jalankan ulang dengan --force.');

            return self::FAILURE;
        }

        $owner = User::query()
            ->where('is_system_owner', true)
            ->orWhere('role', 'admin')
            ->orderByDesc('is_system_owner')
            ->orderBy('id')
            ->first();

        if (! $owner) {
            $this->error('Reset dibatalkan: akun administrator yang akan dipertahankan tidak ditemukan.');

            return self::FAILURE;
        }

        $backupPath = $this->backupDatabase();
        $this->info("Backup database dibuat: storage/app/private/{$backupPath}");

        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                    'stock_transfer_items', 'stock_transfers',
                    'sale_return_items', 'sale_returns',
                    'purchase_return_items', 'purchase_returns',
                    'supplier_payments',
                    'sale_items', 'sales',
                    'purchase_items', 'purchases',
                    'product_batches', 'product_packagings',
                    'stock_opname_items', 'stock_opnames',
                    'stock_outflow_items', 'stock_outflows',
                    'stock_logs', 'cash_transactions', 'cashier_shifts',
                    'owner_capital_transactions', 'promotions', 'activity_logs',
                    'customers', 'products', 'suppliers', 'categories', 'units', 'brands',
                    'store_settings', 'user_permission_overrides',
            ] as $table) {
                DB::table($table)->truncate();
            }

            DB::table('sessions')->truncate();
            DB::table('password_reset_tokens')->truncate();
            DB::table('users')->where('id', '!=', $owner->id)->delete();
            DB::table('users')->where('id', $owner->id)->update(['store_id' => null]);
            DB::table('stores')->truncate();

            $now = now();
            $stores = collect([
                ['code' => 'TOKO-A', 'name' => 'Toko A', 'type' => 'store'],
                ['code' => 'TOKO-B', 'name' => 'Toko B', 'type' => 'store'],
                ['code' => 'GUDANG', 'name' => 'Gudang', 'type' => 'warehouse'],
            ])->map(fn (array $store) => Store::create($store + ['is_active' => true]));

            foreach ($stores as $store) {
                foreach ([
                    'store_name' => $store->name,
                    'phone' => '',
                    'address' => '',
                    'footer' => 'Terima kasih telah berbelanja',
                    'printer_name' => '',
                    'paper_width' => '58',
                ] as $key => $value) {
                    StoreSetting::create(['store_id' => $store->id, 'key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Data operasional lama telah dihapus. Toko A, Toko B, dan Gudang siap digunakan.');
        $this->line("Akun admin {$owner->email} dipertahankan; akun lain dan seluruh sesi telah dihapus.");

        return self::SUCCESS;
    }

    private function backupDatabase(): string
    {
        $tables = DB::select('SHOW TABLES');
        $snapshot = [];

        foreach ($tables as $table) {
            $name = array_values((array) $table)[0];
            $snapshot[$name] = DB::table($name)->get()->map(fn ($row) => (array) $row)->all();
        }

        $path = 'backups/pos-jamu-before-reset-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'captured_at' => now()->toIso8601String(),
            'database' => config('database.connections.'.config('database.default').'.database'),
            'tables' => $snapshot,
        ], JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));

        return $path;
    }
}
