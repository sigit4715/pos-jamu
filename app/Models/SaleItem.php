<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'product_packaging_id', 'product_name', 'unit_name', 'conversion_quantity', 'price', 'quantity', 'base_quantity', 'subtotal'];
    protected function casts(): array { return ['price' => 'decimal:2', 'subtotal' => 'decimal:2', 'quantity' => 'integer', 'conversion_quantity' => 'integer', 'base_quantity' => 'integer']; }
    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function packaging() { return $this->belongsTo(ProductPackaging::class, 'product_packaging_id'); }
}
