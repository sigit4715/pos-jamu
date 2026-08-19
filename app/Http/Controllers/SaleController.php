<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\StockLog;
use App\Services\AuditService;
use App\Services\BatchService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function create()
    {
        $storeId = $this->storeId();
        return view('sales.create', [
            'products' => Product::where('store_id', $storeId)->where('is_active', true)->where('stock', '>', 0)->with(['category', 'packagings' => fn ($query) => $query->where('is_active', true)->orderBy('conversion_quantity')])->orderBy('name')->get(),
            'customers' => Customer::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get(),
            'currentShift' => CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->latest()->first(),
        ]);
    }

    public function index()
    {
        $sales = Sale::where('store_id', $this->storeId())->with('cashier')->latest();
        if (! auth()->user()->hasPermission('sales.view_all')) $sales->where('cashier_id', auth()->id());
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
            'items.*.product_packaging_id' => ['nullable', 'integer', 'exists:product_packagings,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $storeId = $this->storeId();
        $sale = DB::transaction(function () use ($data, $storeId) {
            $items = collect($data['items']);
            $products = Product::where('store_id', $storeId)->whereIn('id', $items->pluck('product_id'))->with(['packagings' => fn ($query) => $query->where('is_active', true)])->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            $promoDiscount = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $packaging = $this->packagingFor($product, $item['product_packaging_id'] ?? null);
                $conversionQuantity = $packaging?->conversion_quantity ?? 1;
                $baseQuantity = (int) $item['quantity'] * $conversionQuantity;
                if (!$product || !$product->is_active || $product->stock < $baseQuantity) {
                    throw ValidationException::withMessages(['items' => 'Stok ' . ($product?->name ?? 'produk') . ' tidak mencukupi.']);
                }
                $line = (float) ($packaging?->price ?? $product->price) * $item['quantity'];
                $subtotal += $line;
                $promotion = Promotion::where('store_id', $storeId)->active()->where(function ($query) use ($product) {
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

            $customer = !empty($data['customer_id']) ? Customer::where('store_id', $storeId)->find($data['customer_id']) : null;
            $shift = CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->latest()->first();
            $sale = Sale::create([
                'store_id' => $storeId,
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
                $packaging = $this->packagingFor($product, $item['product_packaging_id'] ?? null);
                $conversionQuantity = $packaging?->conversion_quantity ?? 1;
                $unitName = $packaging?->name ?? $product->unit;
                $baseQuantity = (int) $item['quantity'] * $conversionQuantity;
                $before = $product->stock;
                $price = (float) ($packaging?->price ?? $product->price);
                $line = $price * $item['quantity'];
                SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_packaging_id' => $packaging?->id, 'product_name' => $product->name, 'unit_name' => $unitName, 'conversion_quantity' => $conversionQuantity, 'price' => $price, 'quantity' => $item['quantity'], 'base_quantity' => $baseQuantity, 'subtotal' => $line]);
                $product->decrement('stock', $baseQuantity);
                app(BatchService::class)->consume($product, $baseQuantity);
                StockLog::create(['store_id' => $storeId, 'product_id' => $product->id, 'user_id' => auth()->id(), 'type' => 'sale', 'quantity_change' => -$baseQuantity, 'transaction_quantity' => $item['quantity'], 'unit_name' => $unitName, 'conversion_quantity' => $conversionQuantity, 'stock_before' => $before, 'stock_after' => $before - $baseQuantity, 'reference' => $sale->invoice_number, 'notes' => 'Penjualan kasir']);
            }
            AuditService::log('sale.created', $sale, 'Transaksi penjualan dibuat', ['total' => $total, 'payment_method' => $data['payment_method']]);
            return $sale;
        });

        return redirect()->route('sales.receipt', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function receipt(Sale $sale)
    {
        abort_unless($sale->store_id === $this->storeId() && (auth()->user()->hasPermission('sales.view_all') || $sale->cashier_id === auth()->id()), 403);
        $sale->load('items', 'cashier');
        $settings = StoreSetting::where('store_id', $this->storeId())->pluck('value', 'key');
        return view('sales.receipt', compact('sale', 'settings'));
    }

    private function storeId(): int
    {
        return app(StoreContext::class)->id();
    }

    private function packagingFor(?Product $product, mixed $packagingId): ?ProductPackaging
    {
        if (blank($packagingId)) {
            return null;
        }

        return $product?->packagings->firstWhere('id', (int) $packagingId)
            ?? throw ValidationException::withMessages(['items' => 'Kemasan barang tidak tersedia pada toko ini.']);
    }
}
