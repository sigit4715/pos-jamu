<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;

class BatchService
{
    public function receive(Product $product, int $quantity, float $unitCost, ?string $batchNumber = null, ?string $manufacturedAt = null, ?string $expiresAt = null, $purchase = null): void
    {
        ProductBatch::create([
            'store_id' => app(StoreContext::class)->id(),
            'product_id' => $product->id,
            'purchase_id' => $purchase?->id,
            'batch_number' => $batchNumber ?: 'AUTO-' . now()->format('YmdHis') . '-' . random_int(10, 99),
            'manufactured_at' => $manufacturedAt,
            'expires_at' => $expiresAt,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]);
    }

    public function consume(Product $product, int $quantity): void
    {
        $remaining = $quantity;
        $batches = ProductBatch::where('store_id', app(StoreContext::class)->id())->where('product_id', $product->id)->where('remaining_quantity', '>', 0)->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->orderBy('id')->lockForUpdate()->get();
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($remaining, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $take);
            $remaining -= $take;
        }
    }

    public function restore(Product $product, int $quantity): void
    {
        $batch = ProductBatch::where('store_id', app(StoreContext::class)->id())->where('product_id', $product->id)->orderByDesc('expires_at')->orderByDesc('id')->lockForUpdate()->first();
        if ($batch) $batch->increment('remaining_quantity', $quantity);
        else $this->receive($product, $quantity, (float) $product->buy_price, 'RETUR-' . now()->format('YmdHis'));
    }

    public function transfer(Product $source, Product $destination, int $quantity, ?int $sourceStoreId = null): void
    {
        $remaining = $quantity;
        $sourceStoreId ??= app(StoreContext::class)->id();
        $batches = ProductBatch::where('store_id', $sourceStoreId)
            ->where('product_id', $source->id)
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $moved = min($remaining, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $moved);

            $destinationBatch = ProductBatch::where('store_id', $destination->store_id)
                ->where('product_id', $destination->id)
                ->where('batch_number', $batch->batch_number)
                ->where('manufactured_at', $batch->manufactured_at)
                ->where('expires_at', $batch->expires_at)
                ->lockForUpdate()
                ->first();

            if ($destinationBatch) {
                $destinationBatch->increment('quantity', $moved);
                $destinationBatch->increment('remaining_quantity', $moved);
            } else {
                ProductBatch::create([
                    'store_id' => $destination->store_id,
                    'product_id' => $destination->id,
                    'batch_number' => $batch->batch_number,
                    'manufactured_at' => $batch->manufactured_at,
                    'expires_at' => $batch->expires_at,
                    'quantity' => $moved,
                    'remaining_quantity' => $moved,
                    'unit_cost' => $batch->unit_cost,
                ]);
            }

            $remaining -= $moved;
        }

        if ($remaining > 0) {
            ProductBatch::create([
                'store_id' => $destination->store_id,
                'product_id' => $destination->id,
                'batch_number' => 'TRANSFER-' . now()->format('YmdHis') . '-' . random_int(10, 99),
                'quantity' => $remaining,
                'remaining_quantity' => $remaining,
                'unit_cost' => $source->buy_price,
            ]);
        }
    }
}
