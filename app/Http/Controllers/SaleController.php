<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\StockLog;
use App\Services\AuditService;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function create()
    {
        return view('sales.create', [
            'products' => Product::where('is_active', true)->where('stock', '>', 0)->with('category')->orderBy('name')->get(),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'currentShift' => CashierShift::where('user_id', auth()->id())->where('status', 'open')->latest()->first(),
        ]);
    }

    public function index()
    {
        $sales = Sale::with('cashier')->latest();
        if (!auth()->user()->isAdmin()) $sales->where('cashier_id', auth()->id());
        return view('sales.index', ['sales' => $sales->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $sale = DB::transaction(function () use ($data) {
            $items = collect($data['items']);
            $products = Product::whereIn('id', $items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $promoDiscount = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (!$product || !$product->is_active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => 'Stok ' . ($product?->name ?? 'produk') . ' tidak mencukupi.']);
                }
                $line = (float) $product->price * $item['quantity'];
                $subtotal += $line;
                $promotion = Promotion::active()->where(function ($query) use ($product) {
                    $query->where('product_id', $product->id)->orWhereNull('product_id');
                })->orderByDesc('value')->first();
                if ($promotion) {
                    $promoDiscount += $promotion->type === 'percentage' ? min($line, $line * ((float) $promotion->value / 100)) : min($line, (float) $promotion->value);
                }
            }

            $manualDiscount = (float) ($data['discount'] ?? 0);
            $discount = min($subtotal, $promoDiscount + $manualDiscount);
            $total = $subtotal - $discount;
            if ($data['paid_amount'] < $total) throw ValidationException::withMessages(['paid_amount' => 'Jumlah pembayaran kurang dari total belanja.']);

            $customer = !empty($data['customer_id']) ? Customer::find($data['customer_id']) : null;
            $shift = CashierShift::where('user_id', auth()->id())->where('status', 'open')->latest()->first();
            $sale = Sale::create([
                'invoice_number' => 'JMU-' . now()->format('YmdHis') . '-' . str_pad((string) (Sale::max('id') + 1), 4, '0', STR_PAD_LEFT),
                'cashier_id' => auth()->id(),
                'shift_id' => $shift?->id,
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name ?? ($data['customer_name'] ?? null),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'paid_amount' => $data['paid_amount'],
                'change_amount' => $data['paid_amount'] - $total,
                'notes' => $data['notes'] ?? null,
            ]);
            if ($customer) $customer->increment('points', (int) floor($total / 10000));

            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                $before = $product->stock;
                $line = $product->price * $item['quantity'];
                SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => $product->price, 'quantity' => $item['quantity'], 'subtotal' => $line]);
                $product->decrement('stock', $item['quantity']);
                app(BatchService::class)->consume($product, $item['quantity']);
                StockLog::create(['product_id' => $product->id, 'user_id' => auth()->id(), 'type' => 'sale', 'quantity_change' => -$item['quantity'], 'stock_before' => $before, 'stock_after' => $before - $item['quantity'], 'reference' => $sale->invoice_number, 'notes' => 'Penjualan kasir']);
            }
            AuditService::log('sale.created', $sale, 'Transaksi penjualan dibuat', ['total' => $total, 'payment_method' => $data['payment_method']]);
            return $sale;
        });

        return redirect()->route('sales.receipt', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function receipt(Sale $sale)
    {
        abort_unless(auth()->user()->isAdmin() || $sale->cashier_id === auth()->id(), 403);
        $sale->load('items', 'cashier');
        $settings = StoreSetting::pluck('value', 'key');
        return view('sales.receipt', compact('sale', 'settings'));
    }
}
