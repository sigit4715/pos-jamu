<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use App\Services\AuditService;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = StoreSetting::pluck('value', 'key');
        return view('settings.index', ['settings' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['store_name' => 'required|string|max:150', 'phone' => 'nullable|string|max:50', 'address' => 'nullable|string|max:500', 'footer' => 'nullable|string|max:300', 'printer_name' => 'nullable|string|max:100', 'paper_width' => 'required|in:58,80']);
        foreach ($data as $key => $value) StoreSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        AuditService::log('settings.updated', null, 'Pengaturan toko diperbarui', ['keys' => array_keys($data)]);
        return back()->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
