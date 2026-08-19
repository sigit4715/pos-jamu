<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutflow extends Model
{
    protected $fillable = ['number', 'user_id', 'reason_type', 'total_qty', 'notes'];
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(StockOutflowItem::class); }
}
