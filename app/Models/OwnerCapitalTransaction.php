<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerCapitalTransaction extends Model
{
    protected $fillable = ['store_id', 'user_id', 'type', 'amount', 'description', 'occurred_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
