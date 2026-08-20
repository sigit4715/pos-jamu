<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\OwnerCapitalTransaction;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StockTransfer;
use App\Services\StoreContext;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $isAdmin = $user->hasPermission('dashboard.view_all');
        $storeId = app(StoreContext::class)->id();
        $activeStore = app(StoreContext::class)->store();
        $visibleStoreIds = $isAdmin
            ? Store::where('is_active', true)->pluck('id')
            : collect([$storeId]);
        $monthStart = now()->startOfMonth();
        $monthEnd = now();

        $scopeSales = function ($query) use ($isAdmin, $user, $storeId, $visibleStoreIds) {
            $query->when($isAdmin,
                fn ($query) => $query->whereIn('store_id', $visibleStoreIds),
                fn ($query) => $query->where('store_id', $storeId),
            );
            if (! $isAdmin) {
                $query->where('cashier_id', $user->id);
            }

            return $query;
        };

        $todaySalesQuery = $scopeSales(Sale::query()->whereDate('created_at', today()));
        $salesToday = (float) (clone $todaySalesQuery)->sum('total');
        $transactionsToday = (int) (clone $todaySalesQuery)->count();
        $todaySales = (clone $todaySalesQuery)->with('items.product')->get();
        $soldProductsToday = (int) $todaySales->sum(fn ($sale) => $sale->items->sum(fn ($item) => $this->baseQuantity($item)));
        $grossProfitToday = (float) $todaySales->sum(fn ($sale) => $sale->items->sum(
            fn ($item) => (float) $item->subtotal - ((float) optional($item->product)->buy_price * $this->baseQuantity($item))
        ));

        $recentSales = $scopeSales(Sale::with('cashier')->latest())->take(6)->get();

        $chart = collect(range(6, 0))->map(function ($offset) use ($scopeSales, $isAdmin, $user, $storeId, $visibleStoreIds) {
            $date = today()->subDays($offset);
            $sales = $scopeSales(Sale::query()->whereDate('created_at', $date));
            $total = (float) (clone $sales)->sum('total');
            $count = (int) (clone $sales)->count();

            $profit = (float) DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                ->when($isAdmin,
                    fn ($query) => $query->whereIn('sales.store_id', $visibleStoreIds),
                    fn ($query) => $query->where('sales.store_id', $storeId),
                )
                ->whereDate('sales.created_at', $date)
                ->when(! $isAdmin, fn ($query) => $query->where('sales.cashier_id', $user->id))
                ->selectRaw('COALESCE(SUM(sale_items.subtotal - (COALESCE(products.buy_price, 0) * COALESCE(NULLIF(sale_items.base_quantity, 0), sale_items.quantity))), 0) as total')
                ->value('total');

            return [
                'label' => $date->format('d M'),
                'total' => $total,
                'profit' => $profit,
                'count' => $count,
            ];
        });

        $monthSales = $scopeSales(Sale::query()->whereBetween('created_at', [$monthStart, $monthEnd]));
        $paymentSummary = (clone $monthSales)
            ->select('payment_method', DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => ucfirst((string) ($row->payment_method ?: 'Tunai')),
                'total' => (float) $row->total,
            ]);

        $categorySummary = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when($isAdmin,
                fn ($query) => $query->whereIn('sales.store_id', $visibleStoreIds),
                fn ($query) => $query->where('sales.store_id', $storeId),
            )
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->when(! $isAdmin, fn ($query) => $query->where('sales.cashier_id', $user->id))
            ->selectRaw("COALESCE(categories.name, 'Lainnya') as name, SUM(sale_items.subtotal) as total")
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(4)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (float) $row->total]);

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when($isAdmin,
                fn ($query) => $query->whereIn('sales.store_id', $visibleStoreIds),
                fn ($query) => $query->where('sales.store_id', $storeId),
            )
            ->when(! $isAdmin, fn ($query) => $query->where('sales.cashier_id', $user->id))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->select('sale_items.product_name', DB::raw('SUM(COALESCE(NULLIF(sale_items.base_quantity, 0), sale_items.quantity)) as qty'))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $cashTransactions = CashTransaction::query()
            ->when($isAdmin,
                fn ($query) => $query->whereIn('store_id', $visibleStoreIds),
                fn ($query) => $query->where('store_id', $storeId),
            )
            ->whereBetween('occurred_at', [$monthStart, $monthEnd]);
        if (! $isAdmin) {
            $cashTransactions->where('user_id', $user->id);
        }
        $cashIncome = (float) (clone $cashTransactions)->where('type', 'income')->sum('amount');
        $cashExpense = (float) (clone $cashTransactions)->where('type', 'expense')->sum('amount');
        $cashSales = (float) (clone $monthSales)->where('payment_method', 'cash')->sum('total');
        $capitalIn = (float) OwnerCapitalTransaction::whereIn('store_id', $visibleStoreIds)->where('type', 'capital_in')->sum('amount');
        $capitalWithdrawals = (float) OwnerCapitalTransaction::whereIn('store_id', $visibleStoreIds)->where('type', 'capital_withdrawal')->sum('amount');
        $lowStockQuery = Product::whereIn('store_id', $visibleStoreIds)->where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock');
        $expiryAlertQuery = ProductBatch::with('product')
            ->whereIn('store_id', $visibleStoreIds)
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [today(), today()->addDays(30)])
            ->orderBy('expires_at');

        return view('dashboard.index', [
            'salesToday' => $salesToday,
            'transactionsToday' => $transactionsToday,
            'grossProfitToday' => $grossProfitToday,
            'soldProductsToday' => $soldProductsToday,
            'productCount' => Product::whereIn('store_id', $visibleStoreIds)->where('is_active', true)->count(),
            'stockValue' => (float) Product::whereIn('store_id', $visibleStoreIds)->where('is_active', true)->selectRaw('COALESCE(SUM(stock * buy_price), 0) as total')->value('total'),
            'cashBalance' => $cashSales + $cashIncome - $cashExpense,
            'ownerCapital' => $capitalIn - $capitalWithdrawals,
            'supplierDebt' => $isAdmin ? (float) Purchase::whereIn('store_id', $visibleStoreIds)->selectRaw('COALESCE(SUM(CASE WHEN total > paid_amount THEN total - paid_amount ELSE 0 END), 0) as total')->value('total') : null,
            'pendingTransferCount' => StockTransfer::whereIn('destination_store_id', $visibleStoreIds)->where('status', 'shipped')->count(),
            'canSendTransfers' => $activeStore->isWarehouse() && $user->hasPermission('stock.transfer'),
            'canReceiveTransfers' => $activeStore->type === 'store' && $user->hasPermission('stock.transfer.receive'),
            'locationSummaries' => $isAdmin ? Store::whereIn('id', $visibleStoreIds)->where('is_active', true)->orderBy('type')->orderBy('name')->get()->map(function (Store $store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'type' => $store->type,
                    'sales_today' => (float) Sale::where('store_id', $store->id)->whereDate('created_at', today())->sum('total'),
                    'transactions_today' => Sale::where('store_id', $store->id)->whereDate('created_at', today())->count(),
                    'stock_value' => (float) Product::where('store_id', $store->id)->where('is_active', true)->selectRaw('COALESCE(SUM(stock * buy_price), 0) as total')->value('total'),
                    'stock_units' => (int) Product::where('store_id', $store->id)->where('is_active', true)->sum('stock'),
                ];
            }) : collect(),
            'lowStockCount' => (clone $lowStockQuery)->count(),
            'lowStock' => $lowStockQuery->limit(5)->get(),
            'expiryAlertCount' => (clone $expiryAlertQuery)->count(),
            'expiryAlerts' => $expiryAlertQuery->limit(5)->get(),
            'recentSales' => $recentSales,
            'chart' => $chart,
            'paymentSummary' => $paymentSummary,
            'categorySummary' => $categorySummary,
            'topProducts' => $topProducts,
            'currentShift' => CashierShift::where('store_id', $storeId)->where('user_id', $user->id)->where('status', 'open')->latest()->first(),
        ]);
    }

    private function baseQuantity(object $item): int
    {
        return (int) ($item->base_quantity ?: $item->quantity);
    }
}
