<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\StoreContext;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = StoreSetting::where('store_id', $this->storeId())->pluck('value', 'key');
        return view('settings.index', ['settings' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['store_name' => 'required|string|max:150', 'phone' => 'nullable|string|max:50', 'address' => 'nullable|string|max:500', 'footer' => 'nullable|string|max:300', 'printer_name' => 'nullable|string|max:100', 'paper_width' => 'required|in:58,80']);
        $storeId = $this->storeId();
        foreach ($data as $key => $value) StoreSetting::updateOrCreate(['store_id' => $storeId, 'key' => $key], ['value' => $value]);
        AuditService::log('settings.updated', null, 'Pengaturan toko diperbarui', ['keys' => array_keys($data)]);
        return back()->with('success', 'Pengaturan toko berhasil disimpan.');
    }
    private function storeId(): int { return app(StoreContext::class)->id(); }
}
