<?php

use App\Http\Controllers\AccessRoleController;
use App\Http\Controllers\AdminMenuPreviewController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OutflowController;
use App\Http\Controllers\OwnerCapitalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierDebtController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/notifikasi-aktivitas', [NotificationController::class, 'index'])->middleware('permission:dashboard.view')->name('notifications.index');
    Route::get('/kasir', [SaleController::class, 'create'])->middleware('permission:sales.create')->name('sales.create');
    Route::post('/kasir', [SaleController::class, 'store'])->middleware('permission:sales.create')->name('sales.store');
    Route::get('/penjualan', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('sales.index');
    Route::get('/penjualan/{sale}/struk', [SaleController::class, 'receipt'])->middleware('permission:sales.view')->name('sales.receipt');

    Route::middleware('permission:shifts.manage')->group(function () {
        Route::get('/shift', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shift/buka', [ShiftController::class, 'open'])->name('shifts.open');
        Route::post('/shift/tutup', [ShiftController::class, 'close'])->name('shifts.close');
    });
    Route::get('/kartu-stok', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock-card.index');
    Route::get('/batch-kedaluwarsa', [BatchController::class, 'index'])->middleware('permission:batches.view')->name('batches.index');
    Route::middleware('permission:cash.manage')->group(function () {
        Route::get('/kas', [CashController::class, 'index'])->name('cash.index');
        Route::post('/kas', [CashController::class, 'store'])->name('cash.store');
    });
    Route::middleware('permission:outflows.manage')->group(function () {
        Route::get('/pengeluaran-barang', [OutflowController::class, 'index'])->name('outflows.index');
        Route::post('/pengeluaran-barang', [OutflowController::class, 'store'])->name('outflows.store');
    });
    Route::middleware('permission:purchases.manage')->group(function () {
        Route::get('/pembelian', [InventoryController::class, 'purchases'])->name('purchases.index');
        Route::post('/pembelian', [InventoryController::class, 'storePurchase'])->name('purchases.store');
    });
    Route::get('/stock-opname', [InventoryController::class, 'opname'])->middleware('permission:stock.opname.view')->name('opname.index');
    Route::post('/stock-opname', [InventoryController::class, 'storeOpname'])->middleware('permission:stock.opname.adjust')->name('opname.store');
    Route::middleware('permission:sales_returns.manage')->group(function () {
        Route::get('/retur-penjualan', [InventoryController::class, 'saleReturns'])->name('sale-returns.index');
        Route::post('/retur-penjualan', [InventoryController::class, 'storeSaleReturn'])->name('sale-returns.store');
    });
    Route::resource('customers', CustomerController::class)->middleware('permission:customers.manage')->except('show', 'destroy');

    Route::middleware('permission:stock.transfer')->group(function () {
        Route::get('/transfer-stok', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::post('/transfer-stok', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::delete('/transfer-stok/{transfer}', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    });
    Route::middleware('permission:stock.transfer.receive')->group(function () {
        Route::get('/penerimaan-transfer', [StockTransferController::class, 'incoming'])->name('stock-transfers.incoming');
        Route::post('/penerimaan-transfer/{transfer}', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
    });
    Route::middleware('permission:purchase_returns.manage')->group(function () {
        Route::get('/retur-pembelian', [InventoryController::class, 'purchaseReturns'])->name('purchase-returns.index');
        Route::post('/retur-pembelian', [InventoryController::class, 'storePurchaseReturn'])->name('purchase-returns.store');
    });

    Route::middleware('permission:stores.manage')->group(function () {
        Route::get('/manajemen-toko', [StoreController::class, 'index'])->name('stores.index');
        Route::post('/manajemen-toko', [StoreController::class, 'store'])->name('stores.store');
        Route::put('/manajemen-toko/{store}', [StoreController::class, 'update'])->name('stores.update');
    });
    Route::post('/ganti-toko', [StoreController::class, 'switch'])->middleware('permission:stores.switch')->name('stores.switch');
    Route::post('/ganti-tampilan-menu', [AdminMenuPreviewController::class, 'update'])->middleware('permission:dashboard.view_all')->name('admin.menu-preview.update');
    Route::resource('users', UserController::class)->middleware(['permission:users.manage', 'role:admin'])->except('show', 'destroy');
    Route::resource('roles', AccessRoleController::class)->middleware('permission:roles.manage')->except('show');
    Route::get('/pengaturan-menu', [MenuItemController::class, 'index'])->middleware('permission:menus.manage')->name('menus.index');
    Route::put('/pengaturan-menu/{menuItem}', [MenuItemController::class, 'update'])->middleware('permission:menus.manage')->name('menus.update');
    Route::middleware('permission:master.manage')->group(function () {
        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master.index');
        Route::post('/master-data/{type}', [MasterDataController::class, 'store'])->name('master.store');
        Route::put('/master-data/{type}/{id}', [MasterDataController::class, 'update'])->name('master.update');
        Route::delete('/master-data/{type}/{id}', [MasterDataController::class, 'destroy'])->name('master.destroy');
    });
    Route::resource('products', ProductController::class)->middleware('permission:products.manage')->except('show', 'destroy');
    Route::middleware('permission:barcodes.view')->group(function () {
        Route::get('/barcode', [BarcodeController::class, 'index'])->name('barcodes.index');
        Route::get('/barcode/{product}', [BarcodeController::class, 'print'])->name('barcodes.print');
    });
    Route::middleware('permission:promotions.manage')->group(function () {
        Route::get('/promo', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promo', [PromotionController::class, 'store'])->name('promotions.store');
        Route::put('/promo/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::delete('/promo/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
    });
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');
    });
    Route::get('/audit-aktivitas', [AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
    Route::middleware('permission:supplier_debts.manage')->group(function () {
        Route::get('/hutang-supplier', [SupplierDebtController::class, 'index'])->name('supplier-debts.index');
        Route::post('/hutang-supplier/{purchase}/bayar', [SupplierDebtController::class, 'pay'])->name('supplier-debts.pay');
    });
    Route::middleware('permission:owner_capital.manage')->group(function () {
        Route::get('/modal-pemilik', [OwnerCapitalController::class, 'index'])->name('owner-capital.index');
        Route::post('/modal-pemilik', [OwnerCapitalController::class, 'store'])->name('owner-capital.store');
    });
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/laporan/penjualan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/pembelian', [ReportController::class, 'purchases'])->name('reports.purchases');
        Route::get('/laporan/stok', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/laporan/keuntungan', [ReportController::class, 'profit'])->name('reports.profit');
        Route::get('/laporan/retur', [ReportController::class, 'returns'])->name('reports.returns');
        Route::get('/laporan/transfer', [ReportController::class, 'transfers'])->name('reports.transfers');
        Route::get('/laporan/arus-kas', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
        Route::get('/laporan/penjualan/export', [ReportController::class, 'exportSalesCsv'])->name('reports.sales.export');
        Route::get('/laporan/penjualan/excel', [ReportController::class, 'exportSalesExcel'])->name('reports.sales.excel');
        Route::get('/laporan/penjualan/pdf', [ReportController::class, 'exportSalesPdf'])->name('reports.sales.pdf');
        Route::get('/laporan/{report}/excel', [ReportController::class, 'exportReportExcel'])->whereIn('report', ['pembelian', 'stok', 'keuntungan', 'arus-kas', 'transfer'])->name('reports.generic.excel');
        Route::get('/laporan/{report}/pdf', [ReportController::class, 'exportReportPdf'])->whereIn('report', ['pembelian', 'stok', 'keuntungan', 'arus-kas', 'transfer'])->name('reports.generic.pdf');
        Route::get('/laporan/pembelian/export', [ReportController::class, 'exportPurchasesCsv'])->name('reports.purchases.export');
        Route::get('/laporan/stok/export', [ReportController::class, 'exportStockCsv'])->name('reports.stock.export');
        Route::get('/laporan/keuntungan/export', [ReportController::class, 'exportProfitCsv'])->name('reports.profit.export');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.legacy');
    });
});
