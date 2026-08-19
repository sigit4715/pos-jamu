<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierDebtController extends Controller
{
    public function index()
    {
        $storeId = $this->storeId();
        $purchases = Purchase::where('store_id', $storeId)->with('supplier', 'payments', 'user')->whereIn('payment_status', ['credit', 'partial'])->latest()->get()->filter(fn ($purchase) => $purchase->outstanding > 0)->values();
        $payments = SupplierPayment::where('store_id', $storeId)->with('supplier', 'purchase', 'user')->latest('paid_at')->paginate(20);
        return view('supplier-debts.index', ['purchases' => $purchases, 'payments' => $payments, 'totalDebt' => $purchases->sum('outstanding')]);
    }

    public function pay(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->store_id === $this->storeId(), 404);
        $data = $request->validate(['amount' => 'required|numeric|min:0.01', 'method' => 'required|in:cash,transfer,giro', 'notes' => 'nullable|string|max:500']);
        if ($data['amount'] > $purchase->outstanding) throw ValidationException::withMessages(['amount' => 'Pembayaran melebihi sisa hutang pembelian.']);
        $storeId = $this->storeId();
        DB::transaction(function () use ($data, $purchase, $storeId) {
            SupplierPayment::create(['store_id' => $storeId, 'supplier_id' => $purchase->supplier_id, 'purchase_id' => $purchase->id, 'user_id' => auth()->id(), 'amount' => $data['amount'], 'paid_at' => now(), 'method' => $data['method'], 'notes' => $data['notes'] ?? null]);
            $paid = (float) $purchase->paid_amount + (float) $data['amount'];
            $purchase->update(['paid_amount' => $paid, 'payment_status' => $paid >= (float) $purchase->total ? 'paid' : 'partial']);
            AuditService::log('supplier_payment.created', $purchase, 'Pembayaran hutang supplier dicatat', ['amount' => $data['amount']]);
        });
        return back()->with('success', 'Pembayaran hutang supplier berhasil dicatat.');
    }
    private function storeId(): int { return app(StoreContext::class)->id(); }
}
