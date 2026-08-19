<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = ['supplier_id', 'purchase_id', 'user_id', 'amount', 'paid_at', 'method', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'paid_at' => 'datetime']; }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function user() { return $this->belongsTo(User::class); }
}
