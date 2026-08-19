<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;

class StoreContext
{
    public function id(): int
    {
        $user = auth()->user();
        abort_unless($user, 401);

        if (! $user->canAccessAllLocations()) {
            abort_unless($user->store_id, 403, 'Akun pengguna belum ditugaskan ke toko atau gudang.');
            abort_unless(Store::where('is_active', true)->whereKey($user->store_id)->exists(), 403, 'Lokasi penugasan pengguna sedang nonaktif.');

            return (int) $user->store_id;
        }

        $activeStoreId = session('active_store_id');
        if ($activeStoreId && Store::where('is_active', true)->whereKey($activeStoreId)->exists()) {
            return (int) $activeStoreId;
        }

        $storeId = Store::where('is_active', true)->orderBy('id')->value('id');
        abort_unless($storeId, 403, 'Belum ada toko aktif yang dapat digunakan.');

        session(['active_store_id' => $storeId]);

        return (int) $storeId;
    }

    public function store(): Store
    {
        return Store::findOrFail($this->id());
    }

    public function stores(): Collection
    {
        return Store::where('is_active', true)->orderBy('name')->get();
    }

    public function switchTo(int $storeId): void
    {
        abort_unless(auth()->user()?->hasPermission('stores.switch'), 403);
        abort_unless(Store::where('is_active', true)->whereKey($storeId)->exists(), 422, 'Toko tidak tersedia.');

        session(['active_store_id' => $storeId]);
    }
}
