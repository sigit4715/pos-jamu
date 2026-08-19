<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSetting;
use App\Services\AuditService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index()
    {
        return view('stores.index', [
            'stores' => Store::withCount(['users', 'products'])->orderBy('name')->get(),
            'activeStoreId' => app(StoreContext::class)->id(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $copyFromStoreId = $request->integer('copy_from_store_id');

        $store = DB::transaction(function () use ($data, $copyFromStoreId) {
            $store = Store::create($data);

            if ($copyFromStoreId && Store::whereKey($copyFromStoreId)->exists()) {
                Product::where('store_id', $copyFromStoreId)->with('packagings')->orderBy('id')->each(function (Product $product) use ($store) {
                    $copy = $product->replicate();
                    $copy->store_id = $store->id;
                    $copy->stock = 0;
                    $copy->save();

                    $product->packagings->each(function ($packaging) use ($copy) {
                        $packagingCopy = $packaging->replicate();
                        $packagingCopy->product_id = $copy->id;
                        $packagingCopy->save();
                    });
                });

                StoreSetting::where('store_id', $copyFromStoreId)->each(function (StoreSetting $setting) use ($store) {
                    StoreSetting::create(['store_id' => $store->id, 'key' => $setting->key, 'value' => $setting->value]);
                });
            }

            return $store;
        });

        AuditService::log('store.created', $store, 'Toko baru ditambahkan', ['copy_from_store_id' => $copyFromStoreId ?: null]);

        return back()->with('success', "Toko {$store->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Store $store)
    {
        $data = $this->validated($request, $store);
        if (! $data['is_active'] && Store::where('is_active', true)->where('id', '!=', $store->id)->doesntExist()) {
            return back()->withErrors(['is_active' => 'Minimal harus ada satu toko aktif.']);
        }

        $store->update($data);
        AuditService::log('store.updated', $store, 'Data toko diperbarui');

        return back()->with('success', "Data {$store->name} berhasil diperbarui.");
    }

    public function switch(Request $request)
    {
        $data = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        app(StoreContext::class)->switchTo((int) $data['store_id']);

        return back()->with('success', 'Toko aktif berhasil diganti.');
    }

    private function validated(Request $request, ?Store $store = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('stores', 'code')->ignore($store?->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:store,warehouse'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
