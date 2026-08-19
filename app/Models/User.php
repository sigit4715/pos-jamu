<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'password', 'role', 'access_role_id', 'store_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sales()
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function accessRole()
    {
        return $this->belongsTo(AccessRole::class);
    }

    public function permissionOverrides()
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function isAdmin(): bool
    {
        return $this->canAccessAllLocations();
    }

    public function isWarehouse(): bool
    {
        return $this->accessRole?->location_type === 'warehouse' || $this->role === 'gudang';
    }

    public function canAccessAllLocations(): bool
    {
        return $this->is_system_owner || $this->accessRole?->location_scope === 'all' || $this->role === 'admin';
    }

    public function hasPermission(string $code): bool
    {
        if ($this->is_system_owner) {
            return true;
        }

        $this->loadMissing(['accessRole.permissions', 'permissionOverrides.permission']);
        $override = $this->permissionOverrides->first(fn (UserPermissionOverride $item) => $item->permission?->code === $code);
        if ($override) {
            return $override->is_allowed;
        }

        return (bool) $this->accessRole?->permissions->contains('code', $code);
    }

    public function roleLabel(): string
    {
        return $this->accessRole?->name ?? match ($this->role) {
            'admin' => 'Administrator',
            'gudang' => 'Petugas Gudang',
            default => 'Kasir',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->access_role_id || ! Schema::hasTable('access_roles')) {
                return;
            }

            $user->access_role_id = AccessRole::where('code', $user->role ?: 'kasir')->value('id');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
