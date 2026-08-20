<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\StoreContext;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasPermission('dashboard.view_all');
        $storeIds = $isAdmin
            ? Store::where('is_active', true)->pluck('id')
            : collect([app(StoreContext::class)->id()]);

        $stores = Store::whereIn('id', $storeIds)->where('is_active', true)->orderBy('name')->get()->map(function (Store $store) {
            $sales = Sale::where('store_id', $store->id)->whereDate('created_at', today());
            $transfersOut = StockTransfer::where('source_store_id', $store->id)->whereDate('transferred_at', today());
            $transfersIn = StockTransfer::where('destination_store_id', $store->id)->whereDate('transferred_at', today());

            return [
                'store' => $store,
                'sales_count' => (clone $sales)->count(),
                'sales_total' => (float) (clone $sales)->sum('total'),
                'transfers_out' => (clone $transfersOut)->count(),
                'transfers_in' => (clone $transfersIn)->count(),
            ];
        });

        $sales = Sale::with(['store', 'cashier'])
            ->whereIn('store_id', $storeIds)
            ->whereDate('created_at', today())
            ->latest()
            ->get()
            ->map(fn (Sale $sale) => [
                'at' => $sale->created_at,
                'location' => $sale->store?->name ?? '-',
                'type' => 'Penjualan',
                'description' => $sale->invoice_number.' · '.($sale->cashier?->name ?? 'Kasir'),
                'amount' => (float) $sale->total,
            ]);

        $transfers = StockTransfer::with(['sourceStore', 'destinationStore', 'user'])
            ->where(function ($query) use ($storeIds) {
                $query->whereIn('source_store_id', $storeIds)->orWhereIn('destination_store_id', $storeIds);
            })
            ->whereDate('transferred_at', today())
            ->latest('transferred_at')
            ->get()
            ->map(fn (StockTransfer $transfer) => [
                'at' => $transfer->transferred_at,
                'location' => ($transfer->sourceStore?->name ?? '-').' → '.($transfer->destinationStore?->name ?? '-'),
                'type' => 'Transfer stok',
                'description' => $transfer->number.' · '.($transfer->user?->name ?? 'Petugas'),
                'amount' => null,
            ]);

        return view('notifications.index', [
            'stores' => $stores,
            'activities' => $sales->concat($transfers)->sortByDesc('at')->values(),
        ]);
    }
}
