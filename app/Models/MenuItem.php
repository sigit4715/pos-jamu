<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['code', 'name', 'section', 'icon', 'route_name', 'route_pattern', 'permission_id', 'sort_order', 'is_active', 'is_system'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_system' => 'boolean'];
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
