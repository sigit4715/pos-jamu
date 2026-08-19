<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'source_product_id', 'destination_product_id', 'product_packaging_id', 'product_name', 'unit_name', 'conversion_quantity', 'quantity', 'base_quantity'];

    public function transfer() { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function sourceProduct() { return $this->belongsTo(Product::class, 'source_product_id'); }
    public function destinationProduct() { return $this->belongsTo(Product::class, 'destination_product_id'); }
    public function packaging() { return $this->belongsTo(ProductPackaging::class, 'product_packaging_id'); }
}
