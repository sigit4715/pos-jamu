<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    protected $fillable = ['store_id', 'product_id', 'user_id', 'type', 'quantity_change', 'transaction_quantity', 'unit_name', 'conversion_quantity', 'stock_before', 'stock_after', 'reference', 'notes'];
    public function product() { return $this->belongsTo(Product::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function user() { return $this->belongsTo(User::class); }
}
