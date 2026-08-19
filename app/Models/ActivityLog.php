<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['store_id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'metadata', 'ip_address'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function user() { return $this->belongsTo(User::class); }
    public function store() { return $this->belongsTo(Store::class); }
}
