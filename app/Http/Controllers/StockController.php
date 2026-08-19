<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use App\Services\StoreContext;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->integer('product_id');
        $movement = $request->input('movement');
        $type = $request->input('type');
        $allowedTypes = ['initial', 'adjustment', 'sale', 'purchase', 'purchase_return', 'sale_return', 'opname', 'outflow', 'transfer_in', 'transfer_out'];
        $storeId = app(StoreContext::class)->id();
        $query = StockLog::where('store_id', $storeId)->with('product', 'user')->latest();
        if ($productId) $query->where('product_id', $productId);
        if ($type === 'other_out') {
            $query->whereIn('type', ['outflow', 'purchase_return', 'transfer_out']);
        } elseif (in_array($type, $allowedTypes, true)) {
            $query->where('type', $type);
        } elseif ($movement === 'in') {
            $query->where('quantity_change', '>', 0);
        } elseif ($movement === 'out') {
            $query->where('quantity_change', '<', 0);
        }
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        return view('stock.index', [
            'products' => Product::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get(),
            'selected' => $productId,
            'movement' => in_array($movement, ['in', 'out'], true) ? $movement : '',
            'type' => in_array($type, [...$allowedTypes, 'other_out'], true) ? $type : '',
            'logs' => $query->paginate(25)->withQueryString(),
            'lowStock' => Product::where('store_id', $storeId)->where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock')->get(),
        ]);
    }
}
