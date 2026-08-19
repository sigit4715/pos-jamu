<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['store_id', 'invoice_number', 'cashier_id', 'shift_id', 'customer_id', 'customer_name', 'subtotal', 'discount', 'total', 'payment_method', 'paid_amount', 'change_amount', 'notes'];
    protected function casts(): array { return ['subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'change_amount' => 'decimal:2']; }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function store() { return $this->belongsTo(Store::class); }
    public function shift() { return $this->belongsTo(CashierShift::class, 'shift_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(SaleItem::class); }
}
