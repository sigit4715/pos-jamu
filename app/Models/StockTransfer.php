<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = ['number', 'source_store_id', 'destination_store_id', 'user_id', 'received_by', 'notes', 'status', 'transferred_at', 'received_at'];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function sourceStore() { return $this->belongsTo(Store::class, 'source_store_id'); }
    public function destinationStore() { return $this->belongsTo(Store::class, 'destination_store_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function items() { return $this->hasMany(StockTransferItem::class); }
}
