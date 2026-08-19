<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = ['product_id', 'purchase_id', 'batch_number', 'manufactured_at', 'expires_at', 'quantity', 'remaining_quantity', 'unit_cost'];
    protected function casts(): array { return ['manufactured_at' => 'date', 'expires_at' => 'date', 'unit_cost' => 'decimal:2']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function getDaysToExpireAttribute(): ?int { return $this->expires_at ? now()->startOfDay()->diffInDays($this->expires_at, false) : null; }
}
