<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['store_id', 'member_code', 'name', 'phone', 'address', 'points', 'is_active'];
    protected function casts(): array { return ['points' => 'integer', 'is_active' => 'boolean']; }
    public function sales() { return $this->hasMany(Sale::class); }
    public function store() { return $this->belongsTo(Store::class); }
}
