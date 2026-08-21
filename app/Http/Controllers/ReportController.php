<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\CashTransaction;
use App\Models\OwnerCapitalTransaction;
use App\Models\Sale;
use App\Models\SupplierPayment;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function returns(Request $request)
    {
        [$from, $to] = $this->period($request);
        $storeId = $this->storeId();
        $purchase = DB::table('purchase_returns as r')->join('suppliers as s', 's.id', '=', 'r.supplier_id')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.store_id', $storeId)->whereBetween('r.created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->select('r.*', 's.name as supplier_name', 'u.name as user_name')->latest('r.created_at')->get();
        $sale = DB::table('sale_returns as r')->join('sales as s', 's.id', '=', 'r.sale_id')->join('users as u', 'u.id', '=', 'r.user_id')->where('r.store_id', $storeId)->whereBetween('r.created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->select('r.*', 's.invoice_number', 'u.name as user_name')->latest('r.created_at')->get();
        return view('reports.returns', compact('from', 'to', 'purchase', 'sale'));
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $storeId = $this->storeId();
        $sales = Sale::where('store_id', $storeId)->with('cashier', 'items.product')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->when($request->payment, fn ($q, $payment) => $q->where('payment_method', $payment))->latest()->get();
        $items = $sales->flatMap(fn ($sale) => $sale->items->map(function ($item) use ($sale) { $item->report_sale = $sale; $item->total_buy = (float) ($item->product?->buy_price ?? 0) * $this->baseQuantity($item); $item->profit = (float) $item->subtotal - $item->total_buy; return $item; }));
        $returns = (float) DB::table('sale_returns')->where('store_id', $storeId)->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->sum('total');
        $paymentSummary = $sales->groupBy('payment_method')->map(fn ($rows) => ['count' => $rows->count(), 'total' => $rows->sum('total')]);
        return view('reports.index', compact('sales', 'items', 'from', 'to', 'returns', 'paymentSummary'));
    }

    public function purchases(Request $request)
    {
        [$from, $to] = $this->period($request);
        $purchases = Purchase::where('store_id', $this->storeId())->with('supplier', 'user', 'items')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        return view('reports.purchases', ['purchases' => $purchases, 'from' => $from, 'to' => $to, 'total' => $purchases->sum('total')]);
    }

    public function stock(Request $request)
    {
        $products = Product::where('store_id', $this->storeId())->with('category', 'supplier')->where('is_active', true)->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))->orderBy('stock')->get();
        return view('reports.stock', ['products' => $products, 'lowCount' => $products->filter(fn ($product) => $product->stock <= $product->minimum_stock)->count(), 'totalBuyValue' => $products->sum(fn ($product) => $product->stock * (float) $product->buy_price), 'totalSaleValue' => $products->sum(fn ($product) => $product->stock * (float) $product->price)]);
    }

    public function profit(Request $request)
    {
        [$from, $to] = $this->period($request);
        $storeId = $this->storeId();
        $sales = Sale::where('store_id', $storeId)->with('items.product', 'cashier')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        $grossSales = (float) $sales->sum('total');
        $cost = (float) $sales->sum(fn ($sale) => $sale->items->sum(fn ($item) => (float) ($item->product?->buy_price ?? 0) * $this->baseQuantity($item)));
        $returns = (float) DB::table('sale_returns')->where('store_id', $storeId)->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->sum('total');
        $operatingExpense = (float) CashTransaction::where('store_id', $storeId)->where('type', 'expense')->whereBetween('occurred_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->sum('amount');
        return view('reports.profit', compact('sales', 'from', 'to', 'grossSales', 'cost', 'returns', 'operatingExpense'));
    }

    public function cashFlow(Request $request)
    {
        [$from, $to] = $this->period($request);
        $storeId = $this->storeId();
        $start = "{$from} 00:00:00";
        $end = "{$to} 23:59:59";

        $salesBefore = (float) Sale::where('store_id', $storeId)->where('payment_method', 'cash')->where('created_at', '<', $start)->sum('total');
        $cashBefore = (float) CashTransaction::where('store_id', $storeId)->where('occurred_at', '<', $start)->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as total")->value('total');
        $capitalBefore = (float) OwnerCapitalTransaction::where('store_id', $storeId)->where('occurred_at', '<', $start)->selectRaw("COALESCE(SUM(CASE WHEN type = 'capital_in' THEN amount ELSE -amount END), 0) as total")->value('total');
        $returnsBefore = (float) DB::table('sale_returns as r')->join('sales as s', 's.id', '=', 'r.sale_id')->where('r.store_id', $storeId)->where('s.payment_method', 'cash')->where('r.created_at', '<', $start)->sum('r.total');
        $supplierBefore = (float) SupplierPayment::where('store_id', $storeId)->where('method', 'cash')->where('paid_at', '<', $start)->sum('amount');
        $opening = $salesBefore + $cashBefore + $capitalBefore - $returnsBefore - $supplierBefore;

        $sales = (float) Sale::where('store_id', $storeId)->where('payment_method', 'cash')->whereBetween('created_at', [$start, $end])->sum('total');
        $manualIncome = (float) CashTransaction::where('store_id', $storeId)->where('type', 'income')->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $manualExpense = (float) CashTransaction::where('store_id', $storeId)->where('type', 'expense')->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $capitalIn = (float) OwnerCapitalTransaction::where('store_id', $storeId)->where('type', 'capital_in')->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $capitalWithdrawal = (float) OwnerCapitalTransaction::where('store_id', $storeId)->where('type', 'capital_withdrawal')->whereBetween('occurred_at', [$start, $end])->sum('amount');
        $returns = (float) DB::table('sale_returns as r')->join('sales as s', 's.id', '=', 'r.sale_id')->where('r.store_id', $storeId)->where('s.payment_method', 'cash')->whereBetween('r.created_at', [$start, $end])->sum('r.total');
        $supplierPayments = (float) SupplierPayment::where('store_id', $storeId)->where('method', 'cash')->whereBetween('paid_at', [$start, $end])->sum('amount');
        $closing = $opening + $sales + $manualIncome + $capitalIn - $manualExpense - $capitalWithdrawal - $returns - $supplierPayments;

        return view('reports.cash-flow', compact('from', 'to', 'opening', 'sales', 'manualIncome', 'manualExpense', 'capitalIn', 'capitalWithdrawal', 'returns', 'supplierPayments', 'closing'));
    }

    public function transfers(Request $request)
    {
        [$from, $to] = $this->period($request);
        $storeId = $this->storeId();
        $transfers = \App\Models\StockTransfer::with(['sourceStore', 'destinationStore', 'user', 'receiver', 'items'])
            ->where(function ($query) use ($storeId) {
                $query->where('source_store_id', $storeId)->orWhere('destination_store_id', $storeId);
            })
            ->whereBetween('transferred_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest('transferred_at')->get();

        return view('reports.transfers', compact('transfers', 'from', 'to'));
    }

    public function exportSalesCsv(Request $request)
    {
        [$from, $to] = $this->period($request);
        $sales = Sale::where('store_id', $this->storeId())->with('cashier', 'items')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Metode', 'Subtotal', 'Diskon', 'Total']);
            foreach ($sales as $sale) fputcsv($out, [$sale->invoice_number, $sale->created_at->format('Y-m-d H:i'), $sale->cashier->name, $sale->customer_name ?: 'Umum', $sale->payment_method, $sale->subtotal, $sale->discount, $sale->total]);
            fclose($out);
        }, "laporan-penjualan-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSalesExcel(Request $request)
    {
        [$from, $to] = $this->period($request);
        $sales = Sale::where('store_id', $this->storeId())->with('cashier')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Penjualan');
        $sheet->setCellValue('A1', 'Laporan Penjualan POS Toko Iwan Jamu');
        $sheet->mergeCells('A1:H1');
        $sheet->fromArray(['Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Metode', 'Subtotal', 'Diskon', 'Total'], null, 'A3');
        $row = 4;
        foreach ($sales as $sale) { $sheet->fromArray([[$sale->invoice_number, $sale->created_at->format('Y-m-d H:i'), $sale->cashier->name, $sale->customer_name ?: 'Umum', $sale->payment_method, $sale->subtotal, $sale->discount, $sale->total]], null, "A{$row}"); $row++; }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:H3')->getFont()->setBold(true);
        $sheet->getStyle("F4:H{$row}")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A', 'H') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        return response()->streamDownload(function () use ($book) { (new Xlsx($book))->save('php://output'); }, "laporan-penjualan-{$from}-{$to}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportSalesPdf(Request $request)
    {
        [$from, $to] = $this->period($request);
        $sales = Sale::where('store_id', $this->storeId())->with('cashier')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        return Pdf::loadView('reports.exports.sales-pdf', compact('sales', 'from', 'to'))->setPaper('a4', 'landscape')->download("laporan-penjualan-{$from}-{$to}.pdf");
    }

    public function exportReportExcel(Request $request, string $report)
    {
        [$title, $headers, $rows] = $this->exportRows($request, $report);
        $book = new Spreadsheet(); $sheet = $book->getActiveSheet(); $sheet->setTitle(substr($title, 0, 30));
        $sheet->setCellValue('A1', $title); $sheet->mergeCellsByColumnAndRow(1, 1, count($headers), 1);
        $sheet->fromArray($headers, null, 'A3'); $sheet->fromArray($rows, null, 'A4');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14); $sheet->getStyleByColumnAndRow(1, 3, count($headers), 3)->getFont()->setBold(true);
        foreach (range(1, count($headers)) as $column) $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        return response()->streamDownload(function () use ($book) { (new Xlsx($book))->save('php://output'); }, "{$report}-".now()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportReportPdf(Request $request, string $report)
    {
        [$title, $headers, $rows] = $this->exportRows($request, $report);
        return Pdf::loadView('reports.exports.table-pdf', compact('title', 'headers', 'rows'))->setPaper('a4', 'landscape')->download("{$report}-".now()->format('Y-m-d').'.pdf');
    }

    private function exportRows(Request $request, string $report): array
    {
        [$from, $to] = $this->period($request); $storeId = $this->storeId();
        return match ($report) {
            'pembelian' => ['Laporan Pembelian', ['Nomor', 'Tanggal', 'Supplier', 'Total', 'Terbayar', 'Sisa'], Purchase::where('store_id', $storeId)->with('supplier')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get()->map(fn ($p) => [$p->number, $p->created_at->format('Y-m-d H:i'), $p->supplier->name, $p->total, $p->paid_amount, $p->outstanding])->all()],
            'stok' => ['Laporan Stok', ['Kode', 'Nama', 'Stok', 'Minimum', 'Harga Modal', 'Nilai Stok'], Product::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get()->map(fn ($p) => [$p->code, $p->name, $p->stock, $p->minimum_stock, $p->buy_price, $p->stock * $p->buy_price])->all()],
            'transfer' => ['Laporan Transfer', ['Nomor', 'Dari', 'Ke', 'Status', 'Tanggal'], \App\Models\StockTransfer::with(['sourceStore', 'destinationStore'])->where(fn ($q) => $q->where('source_store_id', $storeId)->orWhere('destination_store_id', $storeId))->whereBetween('transferred_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest('transferred_at')->get()->map(fn ($t) => [$t->number, $t->sourceStore->name, $t->destinationStore->name, $t->status, $t->transferred_at->format('Y-m-d H:i')])->all()],
            'keuntungan' => ['Laporan Keuntungan', ['Invoice', 'Tanggal', 'Omzet', 'Modal', 'Margin'], Sale::where('store_id', $storeId)->with('items.product')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get()->map(function ($sale) { $cost = $sale->items->sum(fn ($item) => (float) ($item->product?->buy_price ?? 0) * $this->baseQuantity($item)); return [$sale->invoice_number, $sale->created_at->format('Y-m-d H:i'), $sale->total, $cost, $sale->total - $cost]; })->all()],
            default => ['Laporan Arus Kas', ['Keterangan', 'Nilai'], [['Gunakan halaman Arus Kas untuk melihat perhitungan sesuai periode.', '']]],
        };
    }

    public function exportPurchasesCsv(Request $request)
    {
        [$from, $to] = $this->period($request);
        $purchases = Purchase::where('store_id', $this->storeId())->with('supplier', 'user')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        return response()->streamDownload(function () use ($purchases) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nomor', 'Tanggal', 'Supplier', 'Status', 'Jatuh Tempo', 'Total', 'Terbayar', 'Sisa']);
            foreach ($purchases as $purchase) fputcsv($out, [$purchase->number, $purchase->created_at->format('Y-m-d H:i'), $purchase->supplier->name, $purchase->payment_status, $purchase->due_date?->format('Y-m-d'), $purchase->total, $purchase->paid_amount, $purchase->outstanding]);
            fclose($out);
        }, "laporan-pembelian-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportStockCsv(Request $request)
    {
        $products = Product::where('store_id', $this->storeId())->where('is_active', true)->orderBy('name')->get();
        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Nama', 'Stok', 'Minimum', 'Harga Modal', 'Nilai Modal', 'Harga Jual', 'Nilai Jual']);
            foreach ($products as $product) fputcsv($out, [$product->code, $product->name, $product->stock, $product->minimum_stock, $product->buy_price, $product->stock * $product->buy_price, $product->price, $product->stock * $product->price]);
            fclose($out);
        }, 'laporan-stok-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportProfitCsv(Request $request)
    {
        [$from, $to] = $this->period($request);
        $sales = Sale::where('store_id', $this->storeId())->with('cashier', 'items.product')->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest()->get();
        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Tanggal', 'Kasir', 'Omzet', 'Modal', 'Margin']);
            foreach ($sales as $sale) { $cost = $sale->items->sum(fn ($item) => (float) ($item->product?->buy_price ?? 0) * $this->baseQuantity($item)); fputcsv($out, [$sale->invoice_number, $sale->created_at->format('Y-m-d H:i'), $sale->cashier->name, $sale->total, $cost, $sale->total - $cost]); }
            fclose($out);
        }, "laporan-keuntungan-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(Request $request): array
    {
        return [$request->input('from', now()->startOfMonth()->toDateString()), $request->input('to', now()->toDateString())];
    }

    private function storeId(): int
    {
        return app(StoreContext::class)->id();
    }

    private function baseQuantity(object $item): int
    {
        return (int) ($item->base_quantity ?: $item->quantity);
    }
}
