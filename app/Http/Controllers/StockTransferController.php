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
                'status' => 'shipped',
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
                $sourceProduct->decrement('stock', $baseQuantity);

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
            }

            AuditService::log('stock_transfer.created', $transfer, 'Transfer stok gudang ke toko dibuat', ['destination_store_id' => $destination->id]);
        });

        return back()->with('success', 'Transfer stok dikirim dan menunggu penerimaan toko tujuan.');
    }

    public function incoming()
    {
        $store = $this->destinationStore();

        return view('stock-transfers.incoming', [
            'store' => $store,
            'pendingTransfers' => StockTransfer::where('destination_store_id', $store->id)
                ->where('status', 'shipped')
                ->with(['sourceStore', 'user', 'items'])
                ->latest('transferred_at')
                ->get(),
            'receivedTransfers' => StockTransfer::where('destination_store_id', $store->id)
                ->whereIn('status', ['received', 'partial_received'])
                ->with(['sourceStore', 'user', 'items'])
                ->latest('received_at')
                ->take(10)
                ->get(),
        ]);
    }

    public function receive(StockTransfer $transfer)
    {
        $destination = $this->destinationStore();
        abort_unless($transfer->destination_store_id === $destination->id, 404);

        $data = request()->validate([
            'received_quantities' => ['required', 'array'],
            'received_quantities.*' => ['required', 'integer', 'min:0'],
            'difference_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($transfer, $destination, $data) {
            $transfer = StockTransfer::with('items')->lockForUpdate()->findOrFail($transfer->id);
            abort_unless($transfer->status === 'shipped', 422, 'Transfer ini sudah diterima atau tidak dapat diproses.');

            $hasDifference = false;

            foreach ($transfer->items as $item) {
                $receivedQuantity = (int) ($data['received_quantities'][$item->id] ?? -1);
                if ($receivedQuantity < 0 || $receivedQuantity > $item->quantity) {
                    throw ValidationException::withMessages(['received_quantities' => "Jumlah diterima {$item->product_name} tidak valid."]);
                }
                $receivedBaseQuantity = $receivedQuantity * $item->conversion_quantity;
                $hasDifference = $hasDifference || $receivedQuantity !== $item->quantity;
                $sourceProduct = Product::where('store_id', $transfer->source_store_id)->lockForUpdate()->findOrFail($item->source_product_id);
                $destinationProduct = Product::where('store_id', $destination->id)->lockForUpdate()->findOrFail($item->destination_product_id);
                $before = (int) $destinationProduct->stock;
                if ($receivedBaseQuantity > 0) {
                    $destinationProduct->increment('stock', $receivedBaseQuantity);
                    app(BatchService::class)->transfer($sourceProduct, $destinationProduct, $receivedBaseQuantity, $transfer->source_store_id);
                }
                $item->update(['received_quantity' => $receivedQuantity]);

                StockLog::create([
                    'store_id' => $destination->id,
                    'product_id' => $destinationProduct->id,
                    'user_id' => auth()->id(),
                    'type' => 'transfer_in',
                    'quantity_change' => $receivedBaseQuantity,
                    'transaction_quantity' => $receivedQuantity,
                    'unit_name' => $item->unit_name,
                    'conversion_quantity' => $item->conversion_quantity,
                    'stock_before' => $before,
                    'stock_after' => $before + $receivedBaseQuantity,
                    'reference' => $transfer->number,
                    'notes' => "Transfer diterima dari {$transfer->sourceStore?->name}",
                ]);
            }

            $transfer->update(['status' => $hasDifference ? 'partial_received' : 'received', 'notes' => filled($data['difference_notes'] ?? null) ? trim(($transfer->notes ? $transfer->notes."\n" : '').'Selisih penerimaan: '.$data['difference_notes']) : $transfer->notes, 'received_at' => now(), 'received_by' => auth()->id()]);
            AuditService::log($hasDifference ? 'stock_transfer.partially_received' : 'stock_transfer.received', $transfer, $hasDifference ? 'Transfer stok diterima dengan selisih' : 'Transfer stok diterima toko', ['source_store_id' => $transfer->source_store_id]);
        });

        return back()->with('success', 'Penerimaan transfer tersimpan. Stok toko telah diperbarui sesuai jumlah fisik yang diterima.');
    }

    public function cancel(StockTransfer $transfer)
    {
        $sourceStore = $this->warehouse();
        abort_unless($transfer->source_store_id === $sourceStore->id, 404);

        DB::transaction(function () use ($transfer, $sourceStore) {
            $transfer = StockTransfer::with(['items', 'destinationStore'])->lockForUpdate()->findOrFail($transfer->id);
            abort_unless($transfer->status === 'shipped', 422, 'Transfer hanya dapat dibatalkan saat masih berstatus Dikirim.');

            foreach ($transfer->items as $item) {
                $product = Product::where('store_id', $sourceStore->id)->lockForUpdate()->findOrFail($item->source_product_id);
                $before = (int) $product->stock;
                $product->increment('stock', $item->base_quantity);
                StockLog::create([
                    'store_id' => $sourceStore->id, 'product_id' => $product->id, 'user_id' => auth()->id(),
                    'type' => 'transfer_cancel', 'quantity_change' => $item->base_quantity,
                    'transaction_quantity' => $item->quantity, 'unit_name' => $item->unit_name,
                    'conversion_quantity' => $item->conversion_quantity, 'stock_before' => $before,
                    'stock_after' => $before + $item->base_quantity, 'reference' => $transfer->number,
                    'notes' => "Transfer ke {$transfer->destinationStore->name} dibatalkan",
                ]);
            }

            $transfer->update(['status' => 'canceled']);
            AuditService::log('stock_transfer.canceled', $transfer, 'Transfer stok dibatalkan dan stok gudang dikembalikan', ['destination_store_id' => $transfer->destination_store_id]);
        });

        return back()->with('success', 'Transfer dibatalkan. Stok gudang telah dikembalikan.');
    }

    private function warehouse(): Store
    {
        $store = app(StoreContext::class)->store();
        abort_unless($store->isWarehouse(), 403, 'Pilih lokasi Gudang terlebih dahulu untuk melakukan transfer stok.');

        return $store;
    }

    private function destinationStore(): Store
    {
        $store = app(StoreContext::class)->store();
        abort_unless($store->type === 'store', 403, 'Pilih lokasi Toko terlebih dahulu untuk menerima transfer.');

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
