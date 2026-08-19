<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\StockLog;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\AuditService;
use App\Services\BatchService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function index()
    {
        $sourceStore = $this->warehouse();

        return view('stock-transfers.index', [
            'sourceStore' => $sourceStore,
            'products' => Product::where('store_id', $sourceStore->id)->where('is_active', true)->with(['packagings' => fn ($query) => $query->where('is_active', true)])->orderBy('name')->get(),
            'destinations' => Store::where('type', 'store')->where('is_active', true)->orderBy('name')->get(),
            'transfers' => StockTransfer::where('source_store_id', $sourceStore->id)->with(['destinationStore', 'user', 'items'])->latest('transferred_at')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'destination_store_id' => ['required', 'integer', 'exists:stores,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_packaging_id' => ['nullable', 'integer', 'exists:product_packagings,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sourceStore = $this->warehouse();
        $destination = Store::where('type', 'store')->where('is_active', true)->findOrFail($data['destination_store_id']);

        DB::transaction(function () use ($data, $sourceStore, $destination) {
            $transfer = StockTransfer::create([
                'number' => 'TRF-' . now()->format('YmdHis') . '-' . random_int(10, 99),
                'source_store_id' => $sourceStore->id,
                'destination_store_id' => $destination->id,
                'user_id' => auth()->id(),
                'notes' => $data['notes'] ?? null,
                'transferred_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $sourceProduct = Product::where('store_id', $sourceStore->id)->with('packagings')->lockForUpdate()->findOrFail($item['product_id']);
                $packaging = $this->packagingFor($sourceProduct, $item['product_packaging_id'] ?? null);
                $conversionQuantity = $packaging?->conversion_quantity ?? 1;
                $unitName = $packaging?->name ?? $sourceProduct->unit;
                $baseQuantity = (int) $item['quantity'] * $conversionQuantity;

                if ($sourceProduct->stock < $baseQuantity) {
                    throw ValidationException::withMessages(['items' => "Stok {$sourceProduct->name} di gudang tidak mencukupi."]);
                }

                $destinationProduct = $this->destinationProduct($sourceProduct, $destination, $packaging);
                $sourceBefore = (int) $sourceProduct->stock;
                $destinationBefore = (int) $destinationProduct->stock;
                $sourceProduct->decrement('stock', $baseQuantity);
                $destinationProduct->increment('stock', $baseQuantity);
                app(BatchService::class)->transfer($sourceProduct, $destinationProduct, $baseQuantity);

                $transfer->items()->create([
                    'source_product_id' => $sourceProduct->id,
                    'destination_product_id' => $destinationProduct->id,
                    'product_packaging_id' => $packaging?->id,
                    'product_name' => $sourceProduct->name,
                    'unit_name' => $unitName,
                    'conversion_quantity' => $conversionQuantity,
                    'quantity' => $item['quantity'],
                    'base_quantity' => $baseQuantity,
                ]);

                StockLog::create([
                    'store_id' => $sourceStore->id,
                    'product_id' => $sourceProduct->id,
                    'user_id' => auth()->id(),
                    'type' => 'transfer_out',
                    'quantity_change' => -$baseQuantity,
                    'transaction_quantity' => $item['quantity'],
                    'unit_name' => $unitName,
                    'conversion_quantity' => $conversionQuantity,
                    'stock_before' => $sourceBefore,
                    'stock_after' => $sourceBefore - $baseQuantity,
                    'reference' => $transfer->number,
                    'notes' => "Transfer ke {$destination->name}",
                ]);
                StockLog::create([
                    'store_id' => $destination->id,
                    'product_id' => $destinationProduct->id,
                    'user_id' => auth()->id(),
                    'type' => 'transfer_in',
                    'quantity_change' => $baseQuantity,
                    'transaction_quantity' => $item['quantity'],
                    'unit_name' => $unitName,
                    'conversion_quantity' => $conversionQuantity,
                    'stock_before' => $destinationBefore,
                    'stock_after' => $destinationBefore + $baseQuantity,
                    'reference' => $transfer->number,
                    'notes' => "Transfer dari {$sourceStore->name}",
                ]);
            }

            AuditService::log('stock_transfer.created', $transfer, 'Transfer stok gudang ke toko dibuat', ['destination_store_id' => $destination->id]);
        });

        return back()->with('success', 'Transfer stok berhasil diproses.');
    }

    private function warehouse(): Store
    {
        $store = app(StoreContext::class)->store();
        abort_unless($store->isWarehouse(), 403, 'Pilih lokasi Gudang terlebih dahulu untuk melakukan transfer stok.');

        return $store;
    }

    private function packagingFor(Product $product, mixed $packagingId): ?ProductPackaging
    {
        if (blank($packagingId)) {
            return null;
        }

        return $product->packagings->first(fn (ProductPackaging $packaging) => $packaging->id === (int) $packagingId && $packaging->is_active)
            ?? throw ValidationException::withMessages(['items' => 'Kemasan tidak tersedia di gudang ini.']);
    }

    private function destinationProduct(Product $sourceProduct, Store $destination, ?ProductPackaging $packaging): Product
    {
        $destinationProduct = Product::where('store_id', $destination->id)->where('code', $sourceProduct->code)->with('packagings')->lockForUpdate()->first();
        if (! $destinationProduct) {
            $destinationProduct = $sourceProduct->replicate();
            $destinationProduct->store_id = $destination->id;
            $destinationProduct->stock = 0;
            $destinationProduct->save();
            $sourceProduct->packagings->each(function (ProductPackaging $sourcePackaging) use ($destinationProduct) {
                $copy = $sourcePackaging->replicate();
                $copy->product_id = $destinationProduct->id;
                $copy->save();
            });
            $destinationProduct->load('packagings');
        }

        if ($packaging && ! $destinationProduct->packagings->contains('name', $packaging->name)) {
            $copy = $packaging->replicate();
            $copy->product_id = $destinationProduct->id;
            $copy->save();
        }

        return $destinationProduct;
    }
}
