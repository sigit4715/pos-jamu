<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\CashierShift;
use App\Services\AuditService;
use App\Services\StoreContext;
use Illuminate\Http\Request;

class CashController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $storeId = $this->storeId();
        $transactions = CashTransaction::where('store_id', $storeId)->with('user')->whereBetween('occurred_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->latest('occurred_at')->paginate(25)->withQueryString();
        $summary = CashTransaction::where('store_id', $storeId)->whereBetween('occurred_at', ["{$from} 00:00:00", "{$to} 23:59:59"])->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END),0) as income, COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END),0) as expense")->first();
        return view('cash.index', ['transactions' => $transactions, 'from' => $from, 'to' => $to, 'income' => $summary->income, 'expense' => $summary->expense, 'currentShift' => CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->latest()->first()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['type' => 'required|in:income,expense', 'category' => 'required|string|max:100', 'amount' => 'required|numeric|min:0.01', 'description' => 'nullable|string|max:255']);
        $storeId = $this->storeId();
        $shift = CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->latest()->first();
        $transaction = CashTransaction::create(['store_id' => $storeId, 'shift_id' => $shift?->id, 'user_id' => auth()->id(), 'type' => $data['type'], 'category' => $data['category'], 'amount' => $data['amount'], 'description' => $data['description'] ?? null, 'occurred_at' => now()]);
        AuditService::log('cash_transaction.created', $transaction, 'Transaksi kas dicatat', ['type' => $data['type'], 'amount' => $data['amount']]);
        return back()->with('success', 'Transaksi kas berhasil dicatat.');
    }
    private function storeId(): int { return app(StoreContext::class)->id(); }
}
