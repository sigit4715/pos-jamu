<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\StockLog;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\BatchService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    private function no(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(10, 99);
    }

    public function purchases(Request $request)
    {
        $storeId = $this->storeId();
        $products = Product::where('store_id', $storeId)->where('is_active', true)->with('packagings')->orderBy('name')->get();
        $suggestedProductId = $request->integer('product_id');
        if (! $products->contains('id', $suggestedProductId)) {
            $suggestedProductId = null;
        }

        $productCatalog = $products->mapWithKeys(function (Product $product): array {
            return [$product->id => [
                'name' => $product->name,
                'unit' => $product->unit,
                'stock' => $product->stock,
                'packagings' => $product->packagings->where('is_active', true)->map(fn (ProductPackaging $packaging) => [
                    'id' => $packaging->id,
                    'name' => $packaging->name,
                    'conversion' => $packaging->conversion_quantity,
                ])->values(),
            ]];
        });

        return view('inventory.purchases', [
            'products' => $products,
            'productCatalog' => $productCatalog,
            'suppliers' => Supplier::orderBy('name')->get(),
            'purchases' => Purchase::where('store_id', $storeId)->with('supplier', 'user')->latest()->paginate(15)->withQueryString(),
            'suggestedProductId' => $suggestedProductId,
        ]);
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_packaging_id' => 'nullable|exists:product_packagings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:80',
            'items.*.manufactured_at' => 'nullable|date',
            'items.*.expires_at' => 'nullable|date',
            'payment_status' => 'nullable|in:paid,partial,credit',
            'due_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $storeId = $this->storeId();
        DB::transaction(function () use ($data, $storeId) {
            $purchase = Purchase::create([
                'store_id' => $storeId,
                'number' => $this->no('PB'),
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);
            $total = 0;

            foreach ($data['items'] as $item) {
                $product = Product::where('store_id', $storeId)->lockForUpdate()->findOrFail($item['product_id']);
                $packaging = $this->packagingFor($product, $item['product_packaging_id'] ?? null);
                $conversionQuantity = $packaging?->conversion_quantity ?? 1;
                $unitName = $packaging?->name ?? $product->unit;
                $baseQuantity = (int) $item['quantity'] * $conversionQuantity;
                $before = $product->stock;
                $subtotal = $item['quantity'] * $item['price'];
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_packaging_id' => $packaging?->id,
                    'product_name' => $product->name,
                    'unit_name' => $unitName,
                    'conversion_quantity' => $conversionQuantity,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'base_quantity' => $baseQuantity,
                    'subtotal' => $subtotal,
                ]);
                $product->increment('stock', $baseQuantity);
                StockLog::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'purchase',
                    'quantity_change' => $baseQuantity,
                    'transaction_quantity' => $item['quantity'],
                    'unit_name' => $unitName,
                    'conversion_quantity' => $conversionQuantity,
                    'stock_before' => $before,
                    'stock_after' => $before + $baseQuantity,
                    'reference' => $purchase->number,
                    'notes' => 'Pembelian supplier',
                ]);
                app(BatchService::class)->receive($product, $baseQuantity, (float) $item['price'] / $conversionQuantity, $item['batch_number'] ?? null, $item['manufactured_at'] ?? null, $item['expires_at'] ?? null, $purchase);
                $total += $subtotal;
            }

            $requestedPaid = (float) ($data['paid_amount'] ?? 0);
            $paidAmount = ($data['payment_status'] ?? 'paid') === 'paid' ? $total : min($requestedPaid, $total);
            $paymentStatus = $paidAmount >= $total ? 'paid' : ($paidAmount > 0 ? 'partial' : 'credit');
            $purchase->update(['total' => $total, 'payment_status' => $paymentStatus, 'due_date' => $data['due_date'] ?? null, 'paid_amount' => $paidAmount]);
            if ($paidAmount > 0) SupplierPayment::create(['store_id' => $storeId, 'supplier_id' => $purchase->supplier_id, 'purchase_id' => $purchase->id, 'user_id' => auth()->id(), 'amount' => $paidAmount, 'paid_at' => now(), 'method' => 'cash', 'notes' => 'Pembayaran saat pembelian']);
            AuditService::log('purchase.created', $purchase, 'Pembelian supplier dibuat', ['total' => $total, 'payment_status' => $paymentStatus]);
        });

        return back()->with('success', 'Pembelian berhasil disimpan dan stok ditambah.');
    }

    public function purchaseReturns()
    {
        $storeId = $this->storeId();
        return view('inventory.purchase_returns', [
            'purchases' => Purchase::where('store_id', $storeId)->with('supplier', 'items.product')->latest()->get(),
            'returns' => DB::table('purchase_returns as r')
                ->join('suppliers as s', 's.id', '=', 'r.supplier_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->select('r.*', 's.name as supplier_name', 'u.name as user_name')
                ->where('r.store_id', $storeId)
                ->latest('r.created_at')->get(),
        ]);
    }

    public function storePurchaseReturn(Request $request)
    {
        $data = $request->validate([
            'purchase_item_id' => 'required|array|min:1',
            'purchase_item_id.*' => 'exists:purchase_items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'integer|min:0',
            'reason' => 'required|string|max:1000',
        ]);
        $selectedIds = array_values(array_unique(array_map('intval', $data['purchase_item_id'])));

        $storeId = $this->storeId();
        DB::transaction(function () use ($data, $selectedIds, $storeId) {
            $first = PurchaseItem::whereHas('purchase', fn ($query) => $query->where('store_id', $storeId))->lockForUpdate()->findOrFail($selectedIds[0]);
            $purchase = $first->purchase;
            $returnId = DB::table('purchase_returns')->insertGetId([
                'store_id' => $storeId,
                'number' => $this->no('RPB'),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => auth()->id(),
                'total' => 0,
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $total = 0;

            foreach ($selectedIds as $id) {
                $quantity = (int) ($data['quantity'][$id] ?? 0);
                if ($quantity === 0) {
                    continue;
                }
                $item = PurchaseItem::whereHas('purchase', fn ($query) => $query->where('store_id', $storeId))->lockForUpdate()->findOrFail($id);
                $product = Product::where('store_id', $storeId)->lockForUpdate()->findOrFail($item->product_id);
                $conversionQuantity = (int) ($item->conversion_quantity ?: 1);
                $baseQuantity = $quantity * $conversionQuantity;
                if ($item->purchase_id !== $purchase->id || $quantity > $item->quantity - $item->returned_quantity || $baseQuantity > $product->stock) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah retur tidak valid atau stok tidak cukup.']);
                }
                $subtotal = $quantity * $item->price;
                DB::table('purchase_return_items')->insert([
                    'purchase_return_id' => $returnId,
                    'purchase_item_id' => $item->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $item->increment('returned_quantity', $quantity);
                $before = $product->stock;
                $product->decrement('stock', $baseQuantity);
                app(BatchService::class)->consume($product, $baseQuantity);
                StockLog::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'purchase_return',
                    'quantity_change' => -$baseQuantity,
                    'transaction_quantity' => $quantity,
                    'unit_name' => $item->unit_name ?: $product->unit,
                    'conversion_quantity' => $conversionQuantity,
                    'stock_before' => $before,
                    'stock_after' => $before - $baseQuantity,
                    'reference' => 'RPB-' . $returnId,
                    'notes' => $data['reason'],
                ]);
                $total += $subtotal;
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Pilih minimal satu barang dan isi jumlah retur.']);
            }
            DB::table('purchase_returns')->where('id', $returnId)->update(['total' => $total]);
            AuditService::log('purchase_return.created', null, 'Retur pembelian supplier dibuat', ['purchase_return_id' => $returnId, 'total' => $total]);
        });

        return back()->with('success', 'Retur pembelian berhasil diproses.');
    }

    public function saleReturns()
    {
        $storeId = $this->storeId();
        $returnedQuantities = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.store_id', $storeId)
            ->select('sale_item_id', DB::raw('SUM(quantity) as returned_quantity'))
            ->groupBy('sale_item_id')
            ->pluck('returned_quantity', 'sale_item_id');

        $sales = Sale::where('store_id', $storeId)->with('items')->latest()->take(30)->get();
        $sales = $sales->filter(function (Sale $sale) use ($returnedQuantities) {
            $sale->items->each(function (SaleItem $item) use ($returnedQuantities) {
                $item->setAttribute('returned_quantity', (int) ($returnedQuantities[$item->id] ?? 0));
            });
            return $sale->items->contains(fn (SaleItem $item) => $item->returned_quantity < $item->quantity);
        })->values();

        return view('inventory.sale_returns', [
            'sales' => $sales,
            'returns' => DB::table('sale_returns as r')
                ->join('sales as s', 's.id', '=', 'r.sale_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->select('r.*', 's.invoice_number', 'u.name as user_name')
                ->where('r.store_id', $storeId)
                ->latest('r.created_at')->get(),
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        $data = $request->validate([
            'sale_item_id' => 'required|array|min:1',
            'sale_item_id.*' => 'exists:sale_items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'integer|min:0',
            'restock' => 'nullable|array',
            'restock.*' => 'integer',
            'reason' => 'required|string|max:1000',
        ]);
        $selectedIds = array_values(array_unique(array_map('intval', $data['sale_item_id'])));
        $restockIds = array_map('intval', $data['restock'] ?? []);

        $storeId = $this->storeId();
        DB::transaction(function () use ($data, $selectedIds, $restockIds, $storeId) {
            $first = SaleItem::whereHas('sale', fn ($query) => $query->where('store_id', $storeId))->lockForUpdate()->findOrFail($selectedIds[0]);
            $returnId = DB::table('sale_returns')->insertGetId([
                'store_id' => $storeId,
                'number' => $this->no('RJ'),
                'sale_id' => $first->sale_id,
                'user_id' => auth()->id(),
                'total' => 0,
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $total = 0;

            foreach ($selectedIds as $id) {
                $quantity = (int) ($data['quantity'][$id] ?? 0);
                if ($quantity === 0) {
                    continue;
                }
                $item = SaleItem::whereHas('sale', fn ($query) => $query->where('store_id', $storeId))->lockForUpdate()->findOrFail($id);
                $alreadyReturned = (int) DB::table('sale_return_items')->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')->where('sale_returns.store_id', $storeId)->where('sale_item_id', $item->id)->sum('quantity');
                $remaining = $item->quantity - $alreadyReturned;
                if ($item->sale_id !== $first->sale_id || $quantity > $remaining) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah retur penjualan tidak valid atau sudah pernah diretur.']);
                }
                $restock = in_array($item->id, $restockIds, true);
                $subtotal = $quantity * $item->price;
                DB::table('sale_return_items')->insert([
                    'sale_return_id' => $returnId,
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'restock' => $restock,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($restock) {
                    $product = Product::where('store_id', $storeId)->lockForUpdate()->findOrFail($item->product_id);
                    $conversionQuantity = (int) ($item->conversion_quantity ?: 1);
                    $baseQuantity = $quantity * $conversionQuantity;
                    $before = $product->stock;
                    $product->increment('stock', $baseQuantity);
                    app(BatchService::class)->restore($product, $baseQuantity);
                    StockLog::create([
                        'store_id' => $storeId,
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'sale_return',
                        'quantity_change' => $baseQuantity,
                        'transaction_quantity' => $quantity,
                        'unit_name' => $item->unit_name ?: $product->unit,
                        'conversion_quantity' => $conversionQuantity,
                        'stock_before' => $before,
                        'stock_after' => $before + $baseQuantity,
                        'reference' => 'RJ-' . $returnId,
                        'notes' => $data['reason'],
                    ]);
                }
                $total += $subtotal;
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Pilih minimal satu barang dan isi jumlah retur.']);
            }
            DB::table('sale_returns')->where('id', $returnId)->update(['total' => $total]);
            AuditService::log('sale_return.created', null, 'Retur penjualan dibuat', ['sale_return_id' => $returnId, 'total' => $total]);
        });

        return back()->with('success', 'Retur penjualan berhasil diproses.');
    }

    public function opname(Request $request)
    {
        $storeId = $this->storeId();
        $search = trim((string) $request->input('search', ''));
        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $movementSummary = StockLog::where('store_id', $storeId)
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN type = 'purchase' AND quantity_change > 0 THEN quantity_change ELSE 0 END) as purchase_in")
            ->selectRaw("SUM(CASE WHEN type = 'sale' AND quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as sale_out")
            ->selectRaw("SUM(CASE WHEN type IN ('outflow', 'purchase_return') AND quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as other_out")
            ->groupBy('product_id');

        $products = Product::query()
            ->leftJoinSub($movementSummary, 'movement', fn ($join) => $join->on('movement.product_id', '=', 'products.id'))
            ->select([
                'products.*',
                DB::raw('COALESCE(movement.purchase_in, 0) as purchase_in'),
                DB::raw('COALESCE(movement.sale_out, 0) as sale_out'),
                DB::raw('COALESCE(movement.other_out, 0) as other_out'),
            ])
            ->where('products.store_id', $storeId)
            ->where('products.is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($productQuery) use ($search) {
                    $productQuery
                        ->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.code', 'like', "%{$search}%")
                        ->orWhere('products.barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('products.name')
            ->paginate($perPage)
            ->withQueryString();

        return view('inventory.opname', [
            'products' => $products,
            'canEdit' => auth()->user()->hasPermission('stock.opname.adjust'),
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function storeOpname(Request $request)
    {
        $data = $request->validate(['physical' => 'required|array', 'reason' => 'required|array']);
        $storeId = $this->storeId();
        DB::transaction(function () use ($data, $storeId) {
            $id = DB::table('stock_opnames')->insertGetId([
                'store_id' => $storeId,
                'number' => $this->no('SO'),
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($data['physical'] as $productId => $physical) {
                $product = Product::where('store_id', $storeId)->lockForUpdate()->findOrFail($productId);
                $physical = (int) $physical;
                $difference = $physical - $product->stock;
                if (!$difference) {
                    continue;
                }
                DB::table('stock_opname_items')->insert([
                    'stock_opname_id' => $id,
                    'product_id' => $productId,
                    'system_stock' => $product->stock,
                    'physical_stock' => $physical,
                    'difference' => $difference,
                    'reason' => $data['reason'][$productId] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $before = $product->stock;
                $product->update(['stock' => $physical]);
                StockLog::create([
                    'store_id' => $storeId,
                    'product_id' => $productId,
                    'user_id' => auth()->id(),
                    'type' => 'opname',
                    'quantity_change' => $difference,
                    'transaction_quantity' => abs($difference),
                    'unit_name' => $product->unit,
                    'conversion_quantity' => 1,
                    'stock_before' => $before,
                    'stock_after' => $physical,
                    'reference' => 'SO-' . $id,
                    'notes' => $data['reason'][$productId] ?? 'Stock opname',
                ]);
            }
            AuditService::log('stock_opname.created', null, 'Stock opname disimpan', ['stock_opname_id' => $id]);
        });

        return back()->with('success', 'Stock opname tersimpan.');
    }

    private function storeId(): int
    {
        return app(StoreContext::class)->id();
    }

    private function packagingFor(Product $product, mixed $packagingId): ?ProductPackaging
    {
        if (blank($packagingId)) {
            return null;
        }

        return ProductPackaging::where('product_id', $product->id)
            ->where('is_active', true)
            ->findOrFail((int) $packagingId);
    }
}
