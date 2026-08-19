<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermissionOverride extends Model
{
    protected $fillable = ['user_id', 'permission_id', 'is_allowed'];

    protected function casts(): array
    {
        return ['is_allowed' => 'boolean'];
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
