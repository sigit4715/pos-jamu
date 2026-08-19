<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = ['product_id', 'name', 'type', 'value', 'starts_at', 'ends_at', 'is_active'];
    protected function casts(): array { return ['value' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function scopeActive($query) { return $query->where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now())); }
}
