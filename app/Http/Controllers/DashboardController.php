<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\OwnerCapitalTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $monthStart = now()->startOfMonth();
        $monthEnd = now();

        $scopeSales = function ($query) use ($isAdmin, $user) {
            if (! $isAdmin) {
                $query->where('cashier_id', $user->id);
            }

            return $query;
        };

        $todaySalesQuery = $scopeSales(Sale::query()->whereDate('created_at', today()));
        $salesToday = (float) (clone $todaySalesQuery)->sum('total');
        $transactionsToday = (int) (clone $todaySalesQuery)->count();
        $todaySales = (clone $todaySalesQuery)->with('items.product')->get();
        $soldProductsToday = (int) $todaySales->sum(fn ($sale) => $sale->items->sum('quantity'));
        $grossProfitToday = (float) $todaySales->sum(fn ($sale) => $sale->items->sum(
            fn ($item) => (float) $item->subtotal - ((float) optional($item->product)->buy_price * (int) $item->quantity)
        ));

        $recentSales = $scopeSales(Sale::with('cashier')->latest())->take(6)->get();

        $chart = collect(range(6, 0))->map(function ($offset) use ($scopeSales, $isAdmin, $user) {
            $date = today()->subDays($offset);
            $sales = $scopeSales(Sale::query()->whereDate('created_at', $date));
            $total = (float) (clone $sales)->sum('total');
            $count = (int) (clone $sales)->count();

            $profit = (float) DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                ->whereDate('sales.created_at', $date)
                ->when(! $isAdmin, fn ($query) => $query->where('sales.cashier_id', $user->id))
                ->selectRaw('COALESCE(SUM(sale_items.subtotal - (COALESCE(products.buy_price, 0) * sale_items.quantity)), 0) as total')
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
            ->when(! $isAdmin, fn ($query) => $query->where('sales.cashier_id', $user->id))
            ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
            ->select('sale_items.product_name', DB::raw('SUM(sale_items.quantity) as qty'))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $cashTransactions = CashTransaction::query()->whereBetween('occurred_at', [$monthStart, $monthEnd]);
        if (! $isAdmin) {
            $cashTransactions->where('user_id', $user->id);
        }
        $cashIncome = (float) (clone $cashTransactions)->where('type', 'income')->sum('amount');
        $cashExpense = (float) (clone $cashTransactions)->where('type', 'expense')->sum('amount');
        $cashSales = (float) (clone $monthSales)->where('payment_method', 'cash')->sum('total');
        $capitalIn = (float) OwnerCapitalTransaction::where('type', 'capital_in')->sum('amount');
        $capitalWithdrawals = (float) OwnerCapitalTransaction::where('type', 'capital_withdrawal')->sum('amount');

        return view('dashboard.index', [
            'salesToday' => $salesToday,
            'transactionsToday' => $transactionsToday,
            'grossProfitToday' => $grossProfitToday,
            'soldProductsToday' => $soldProductsToday,
            'productCount' => Product::where('is_active', true)->count(),
            'stockValue' => (float) Product::where('is_active', true)->selectRaw('COALESCE(SUM(stock * buy_price), 0) as total')->value('total'),
            'cashBalance' => $cashSales + $cashIncome - $cashExpense,
            'ownerCapital' => $capitalIn - $capitalWithdrawals,
            'supplierDebt' => $isAdmin ? (float) Purchase::selectRaw('COALESCE(SUM(CASE WHEN total > paid_amount THEN total - paid_amount ELSE 0 END), 0) as total')->value('total') : null,
            'lowStock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock')->limit(5)->get(),
            'expiryAlerts' => ProductBatch::with('product')->where('remaining_quantity', '>', 0)->whereNotNull('expires_at')->whereBetween('expires_at', [today(), today()->addDays(30)])->orderBy('expires_at')->limit(5)->get(),
            'recentSales' => $recentSales,
            'chart' => $chart,
            'paymentSummary' => $paymentSummary,
            'categorySummary' => $categorySummary,
            'topProducts' => $topProducts,
            'currentShift' => CashierShift::where('user_id', $user->id)->where('status', 'open')->latest()->first(),
        ]);
    }
}
