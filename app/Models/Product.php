<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['category_id', 'unit_id', 'brand_id', 'supplier_id', 'code', 'barcode', 'name', 'description', 'price', 'buy_price', 'stock', 'minimum_stock', 'unit', 'is_active'];
    protected function casts(): array { return ['price' => 'decimal:2', 'buy_price' => 'decimal:2', 'is_active' => 'boolean']; }
    public function category() { return $this->belongsTo(Category::class); }
    public function unitRelation() { return $this->belongsTo(Unit::class, 'unit_id'); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function saleItems() { return $this->hasMany(SaleItem::class); }
    public function batches() { return $this->hasMany(ProductBatch::class); }
    public function promotions() { return $this->hasMany(Promotion::class); }
}
