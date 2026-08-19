<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $days = max(1, min(365, (int) $request->input('days', 30)));
        $batches = ProductBatch::with('product')->where('remaining_quantity', '>', 0)->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->paginate(25)->withQueryString();
        $expired = ProductBatch::with('product')->where('remaining_quantity', '>', 0)->whereNotNull('expires_at')->whereDate('expires_at', '<', today())->count();
        $soon = ProductBatch::where('remaining_quantity', '>', 0)->whereNotNull('expires_at')->whereBetween('expires_at', [today(), today()->addDays($days)])->count();
        return view('batches.index', ['batches' => $batches, 'products' => Product::where('is_active', true)->orderBy('name')->get(), 'expired' => $expired, 'soon' => $soon, 'days' => $days]);
    }
}
