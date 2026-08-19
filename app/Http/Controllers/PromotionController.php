<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\AuditService;
use App\Services\StoreContext;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index() { $storeId = $this->storeId(); return view('promotions.index', ['promotions' => Promotion::where('store_id', $storeId)->with('product')->latest()->get(), 'products' => Product::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $data = $this->validated($request); $data['store_id'] = $this->storeId();
        $promotion = Promotion::create($data);
        AuditService::log('promotion.created', $promotion, 'Promo dibuat');
        return back()->with('success', 'Promo berhasil ditambahkan.');
    }

    public function update(Request $request, Promotion $promotion)
    {
        $this->ensureStore($promotion);
        $promotion->update($this->validated($request));
        AuditService::log('promotion.updated', $promotion, 'Promo diperbarui');
        return back()->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promotion $promotion)
    {
        $this->ensureStore($promotion);
        $promotion->update(['is_active' => false]);
        AuditService::log('promotion.archived', $promotion, 'Promo dinonaktifkan');
        return back()->with('success', 'Promo dinonaktifkan.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['product_id' => 'nullable|exists:products,id', 'name' => 'required|string|max:150', 'type' => 'required|in:percentage,fixed', 'value' => 'required|numeric|min:0', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'nullable|boolean']);
        if (!empty($data['product_id'])) abort_unless(Product::where('store_id', $this->storeId())->whereKey($data['product_id'])->exists(), 422, 'Produk bukan milik toko aktif.');
        return $data;
    }
    private function storeId(): int { return app(StoreContext::class)->id(); }
    private function ensureStore(Promotion $promotion): void { abort_unless($promotion->store_id === $this->storeId(), 404); }
}
