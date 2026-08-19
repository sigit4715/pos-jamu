<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = ['number', 'source_store_id', 'destination_store_id', 'user_id', 'notes', 'transferred_at'];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    public function sourceStore() { return $this->belongsTo(Store::class, 'source_store_id'); }
    public function destinationStore() { return $this->belongsTo(Store::class, 'destination_store_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(StockTransferItem::class); }
}
