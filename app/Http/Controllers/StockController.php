<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->integer('product_id');
        $query = StockLog::with('product', 'user')->latest();
        if ($productId) $query->where('product_id', $productId);
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        return view('stock.index', ['products' => Product::where('is_active', true)->orderBy('name')->get(), 'selected' => $productId, 'logs' => $query->paginate(25)->withQueryString(), 'lowStock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock')->get()]);
    }
}
