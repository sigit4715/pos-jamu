<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['code', 'name', 'group_name', 'description'];

    public function roles()
    {
        return $this->belongsToMany(AccessRole::class, 'role_permission');
    }
}
