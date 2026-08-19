<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;

class BatchService
{
    public function receive(Product $product, int $quantity, float $unitCost, ?string $batchNumber = null, ?string $manufacturedAt = null, ?string $expiresAt = null, $purchase = null): void
    {
        ProductBatch::create([
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
        $batches = ProductBatch::where('product_id', $product->id)->where('remaining_quantity', '>', 0)->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->orderBy('id')->lockForUpdate()->get();
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($remaining, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $take);
            $remaining -= $take;
        }
    }

    public function restore(Product $product, int $quantity): void
    {
        $batch = ProductBatch::where('product_id', $product->id)->orderByDesc('expires_at')->orderByDesc('id')->lockForUpdate()->first();
        if ($batch) $batch->increment('remaining_quantity', $quantity);
        else $this->receive($product, $quantity, (float) $product->buy_price, 'RETUR-' . now()->format('YmdHis'));
    }
}
