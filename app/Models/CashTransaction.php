<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = ['store_id', 'shift_id', 'user_id', 'type', 'category', 'amount', 'description', 'occurred_at'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'occurred_at' => 'datetime']; }
    public function shift() { return $this->belongsTo(CashierShift::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function user() { return $this->belongsTo(User::class); }
}
