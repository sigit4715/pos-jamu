<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\User;
use App\Models\StoreSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@posjamu.test'], ['name' => 'Admin Jamu', 'role' => 'admin', 'password' => Hash::make('password')]);
        User::updateOrCreate(['email' => 'kasir@posjamu.test'], ['name' => 'Kasir Jamu', 'role' => 'kasir', 'password' => Hash::make('password')]);
        foreach (['store_name' => 'POS Jamu', 'phone' => '', 'address' => '', 'footer' => 'Terima kasih telah berbelanja', 'printer_name' => '', 'paper_width' => '58'] as $key => $value) StoreSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Supplier::updateOrCreate(['name' => 'Supplier Jamu Utama'], ['phone' => '0812-0000-0000', 'address' => 'Kota Anda']);
        Unit::firstOrCreate(['name' => 'Botol'], ['symbol' => 'btl', 'is_active' => true]);
        Unit::firstOrCreate(['name' => 'Sachet'], ['symbol' => 'sct', 'is_active' => true]);
        Brand::firstOrCreate(['name' => 'Jamu Nusantara'], ['description' => 'Merek demo', 'is_active' => true]);
        $herbal = Category::firstOrCreate(['name' => 'Jamu Herbal'], ['description' => 'Racikan herbal tradisional']);
        $serbuk = Category::firstOrCreate(['name' => 'Jamu Serbuk'], ['description' => 'Jamu siap seduh']);
        foreach ([[$herbal, 'JMU-001', 'Jamu Kunyit Asam', 12000, 28, 'botol'], [$herbal, 'JMU-002', 'Jamu Beras Kencur', 10000, 24, 'botol'], [$herbal, 'JMU-003', 'Jamu Temulawak', 13000, 18, 'botol'], [$serbuk, 'JMU-004', 'Wedang Uwuh', 15000, 12, 'sachet'], [$serbuk, 'JMU-005', 'Jamu Jahe Merah', 11000, 5, 'sachet']] as [$category, $code, $name, $price, $stock, $unit]) {
            $product = Product::firstOrCreate(['code' => $code], ['category_id' => $category->id, 'name' => $name, 'price' => $price, 'buy_price' => $price, 'stock' => $stock, 'minimum_stock' => 5, 'unit' => $unit, 'is_active' => true]);
            if ($product->wasRecentlyCreated) StockLog::create(['product_id' => $product->id, 'user_id' => $admin->id, 'type' => 'initial', 'quantity_change' => $stock, 'stock_before' => 0, 'stock_after' => $stock, 'reference' => 'SEED', 'notes' => 'Stok awal demo']);
        }
    }
}
