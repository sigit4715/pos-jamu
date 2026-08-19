<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index() { return view('promotions.index', ['promotions' => Promotion::with('product')->latest()->get(), 'products' => Product::where('is_active', true)->orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $promotion = Promotion::create($this->validated($request));
        AuditService::log('promotion.created', $promotion, 'Promo dibuat');
        return back()->with('success', 'Promo berhasil ditambahkan.');
    }

    public function update(Request $request, Promotion $promotion)
    {
        $promotion->update($this->validated($request));
        AuditService::log('promotion.updated', $promotion, 'Promo diperbarui');
        return back()->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->update(['is_active' => false]);
        AuditService::log('promotion.archived', $promotion, 'Promo dinonaktifkan');
        return back()->with('success', 'Promo dinonaktifkan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['product_id' => 'nullable|exists:products,id', 'name' => 'required|string|max:150', 'type' => 'required|in:percentage,fixed', 'value' => 'required|numeric|min:0', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'nullable|boolean']);
    }
}
