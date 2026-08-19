<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\StockOutflow;
use App\Models\StockOutflowItem;
use App\Services\AuditService;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutflowController extends Controller
{
    public function index()
    {
        return view('outflows.index', ['products' => Product::where('is_active', true)->orderBy('name')->get(), 'outflows' => StockOutflow::with('user', 'items')->latest()->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['reason_type' => 'required|in:rusak,kadaluarsa,pemakaian_internal,sampel,bonus,lainnya', 'notes' => 'nullable|string|max:500', 'items' => 'required|array|min:1', 'items.*.product_id' => 'required|exists:products,id', 'items.*.quantity' => 'required|integer|min:1']);
        DB::transaction(function () use ($data) {
            $outflow = StockOutflow::create(['number' => 'PBK-' . now()->format('YmdHis') . '-' . random_int(10, 99), 'user_id' => auth()->id(), 'reason_type' => $data['reason_type'], 'notes' => $data['notes'] ?? null, 'total_qty' => 0]);
            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) throw ValidationException::withMessages(['items' => 'Stok ' . $product->name . ' tidak mencukupi.']);
                $before = $product->stock;
                StockOutflowItem::create(['stock_outflow_id' => $outflow->id, 'product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $item['quantity']]);
                $product->decrement('stock', $item['quantity']);
                app(BatchService::class)->consume($product, $item['quantity']);
                StockLog::create(['product_id' => $product->id, 'user_id' => auth()->id(), 'type' => 'outflow', 'quantity_change' => -$item['quantity'], 'stock_before' => $before, 'stock_after' => $before - $item['quantity'], 'reference' => $outflow->number, 'notes' => $data['reason_type'] . ($data['notes'] ? ' - ' . $data['notes'] : '')]);
                $total += $item['quantity'];
            }
            $outflow->update(['total_qty' => $total]);
            AuditService::log('stock_outflow.created', $outflow, 'Pengeluaran barang dicatat', ['total_qty' => $total, 'reason_type' => $data['reason_type']]);
        });
        return back()->with('success', 'Pengeluaran barang berhasil dicatat dan stok diperbarui.');
    }
}
