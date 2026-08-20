<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductPackaging;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDemoStoreTransfer extends Command
{
    protected $signature = 'pos:seed-demo-transfer {--force : Isi data contoh hanya pada setup yang masih kosong}';

    protected $description = 'Isi produk contoh gudang serta simulasi transfer ke Toko A dan Toko B';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Jalankan ulang dengan --force untuk membuat data contoh.');

            return self::FAILURE;
        }

        if (Product::exists() || StockTransfer::exists()) {
            $this->error('Data contoh dibatalkan agar data operasional yang sudah ada tidak berubah.');

            return self::FAILURE;
        }

        $stores = Store::whereIn('code', ['TOKO-A', 'TOKO-B', 'GUDANG'])->get()->keyBy('code');
        if ($stores->count() !== 3) {
            $this->error('Toko A, Toko B, atau Gudang belum tersedia.');

            return self::FAILURE;
        }

        $warehouseUser = User::where('role', 'gudang')->orderBy('id')->first();
        if (! $warehouseUser) {
            $this->error('Akun Petugas Gudang belum tersedia.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($stores, $warehouseUser) {
            $category = Category::firstOrCreate(['name' => 'Jamu Siap Minum'], ['description' => 'Produk contoh untuk simulasi multi lokasi']);
            $unit = Unit::firstOrCreate(['name' => 'Botol'], ['symbol' => 'btl', 'is_active' => true]);
            $brand = Brand::firstOrCreate(['name' => 'Jamu Iwan'], ['description' => 'Merek contoh', 'is_active' => true]);
            $supplier = Supplier::firstOrCreate(['name' => 'Supplier Jamu Contoh'], ['phone' => '0812-0000-0000', 'address' => 'Data simulasi', 'is_active' => true]);

            $products = collect([
                ['code' => 'JMU-SIM-001', 'name' => 'Jamu Kunyit Asam', 'price' => 15000, 'buy_price' => 9000, 'stock' => 80, 'minimum_stock' => 10],
                ['code' => 'JMU-SIM-002', 'name' => 'Jamu Beras Kencur', 'price' => 14000, 'buy_price' => 8000, 'stock' => 60, 'minimum_stock' => 10],
                ['code' => 'JMU-SIM-003', 'name' => 'Jamu Temulawak', 'price' => 16000, 'buy_price' => 9500, 'stock' => 40, 'minimum_stock' => 8],
            ])->map(function (array $data) use ($stores, $category, $unit, $brand, $supplier, $warehouseUser) {
                $product = Product::create($data + [
                    'store_id' => $stores['GUDANG']->id,
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'barcode' => '899990'.substr($data['code'], -3),
                    'unit' => 'botol',
                    'is_active' => true,
                ]);

                ProductPackaging::create([
                    'product_id' => $product->id,
                    'name' => 'Karton (12 botol)',
                    'conversion_quantity' => 12,
                    'price' => $data['price'] * 12,
                    'is_active' => true,
                ]);
                ProductBatch::create([
                    'store_id' => $stores['GUDANG']->id,
                    'product_id' => $product->id,
                    'batch_number' => 'SIM-'.substr($data['code'], -3),
                    'manufactured_at' => today()->subDays(7),
                    'expires_at' => today()->addMonths(8),
                    'quantity' => $data['stock'],
                    'remaining_quantity' => $data['stock'],
                    'unit_cost' => $data['buy_price'],
                ]);
                StockLog::create([
                    'store_id' => $stores['GUDANG']->id,
                    'product_id' => $product->id,
                    'user_id' => $warehouseUser->id,
                    'type' => 'initial',
                    'quantity_change' => $data['stock'],
                    'transaction_quantity' => $data['stock'],
                    'unit_name' => 'botol',
                    'conversion_quantity' => 1,
                    'stock_before' => 0,
                    'stock_after' => $data['stock'],
                    'reference' => 'SIMULASI-AWAL',
                    'notes' => 'Stok awal data contoh Gudang',
                ]);

                return $product->load('packagings');
            });

            $this->transfer($products, $stores['GUDANG'], $stores['TOKO-A'], $warehouseUser, [20, 12, 8], 'A');
            $this->transfer($products, $stores['GUDANG'], $stores['TOKO-B'], $warehouseUser, [15, 15, 10], 'B');
        });

        $this->info('Produk contoh dan dua transfer Gudang ke Toko berhasil dibuat.');

        return self::SUCCESS;
    }

    private function transfer($products, Store $source, Store $destination, User $user, array $quantities, string $suffix): void
    {
        $transfer = StockTransfer::create([
            'number' => 'TRF-SIM-'.now()->format('YmdHis').'-'.$suffix,
            'source_store_id' => $source->id,
            'destination_store_id' => $destination->id,
            'user_id' => $user->id,
            'notes' => "Simulasi distribusi Gudang ke {$destination->name}",
            'transferred_at' => now(),
        ]);

        foreach ($products->values() as $index => $sourceProduct) {
            $quantity = $quantities[$index];
            $destinationProduct = $sourceProduct->replicate();
            $destinationProduct->store_id = $destination->id;
            $destinationProduct->stock = 0;
            $destinationProduct->save();
            $sourceProduct->packagings->each(function (ProductPackaging $packaging) use ($destinationProduct) {
                $copy = $packaging->replicate();
                $copy->product_id = $destinationProduct->id;
                $copy->save();
            });

            $sourceBefore = (int) $sourceProduct->stock;
            $sourceProduct->decrement('stock', $quantity);
            $destinationProduct->increment('stock', $quantity);
            $sourceBatch = ProductBatch::where('store_id', $source->id)->where('product_id', $sourceProduct->id)->lockForUpdate()->firstOrFail();
            $sourceBatch->decrement('remaining_quantity', $quantity);
            ProductBatch::create([
                'store_id' => $destination->id,
                'product_id' => $destinationProduct->id,
                'batch_number' => $sourceBatch->batch_number,
                'manufactured_at' => $sourceBatch->manufactured_at,
                'expires_at' => $sourceBatch->expires_at,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'unit_cost' => $sourceBatch->unit_cost,
            ]);

            $transfer->items()->create([
                'source_product_id' => $sourceProduct->id,
                'destination_product_id' => $destinationProduct->id,
                'product_name' => $sourceProduct->name,
                'unit_name' => 'botol',
                'conversion_quantity' => 1,
                'quantity' => $quantity,
                'base_quantity' => $quantity,
            ]);
            StockLog::insert([
                [
                    'store_id' => $source->id, 'product_id' => $sourceProduct->id, 'user_id' => $user->id,
                    'type' => 'transfer_out', 'quantity_change' => -$quantity, 'transaction_quantity' => $quantity,
                    'unit_name' => 'botol', 'conversion_quantity' => 1, 'stock_before' => $sourceBefore,
                    'stock_after' => $sourceBefore - $quantity, 'reference' => $transfer->number,
                    'notes' => "Transfer ke {$destination->name}", 'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'store_id' => $destination->id, 'product_id' => $destinationProduct->id, 'user_id' => $user->id,
                    'type' => 'transfer_in', 'quantity_change' => $quantity, 'transaction_quantity' => $quantity,
                    'unit_name' => 'botol', 'conversion_quantity' => 1, 'stock_before' => 0,
                    'stock_after' => $quantity, 'reference' => $transfer->number,
                    'notes' => "Transfer dari {$source->name}", 'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
        }
    }
}
