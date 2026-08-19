<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $fillable = ['user_id', 'opened_at', 'closed_at', 'opening_cash', 'expected_cash', 'closing_cash', 'difference', 'status', 'notes'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'opening_cash' => 'decimal:2', 'expected_cash' => 'decimal:2', 'closing_cash' => 'decimal:2', 'difference' => 'decimal:2'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function sales() { return $this->hasMany(Sale::class, 'shift_id'); }
}
