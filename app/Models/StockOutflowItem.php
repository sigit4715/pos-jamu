<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutflowItem extends Model
{
    protected $fillable = ['stock_outflow_id', 'product_id', 'product_name', 'quantity'];
    public function outflow() { return $this->belongsTo(StockOutflow::class, 'stock_outflow_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
