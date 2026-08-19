<?php

namespace App\Http\Controllers;

use App\Models\OwnerCapitalTransaction;
use App\Services\AuditService;
use Illuminate\Http\Request;

class OwnerCapitalController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $query = OwnerCapitalTransaction::with('user')
            ->whereBetween('occurred_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
        $capitalIn = (float) (clone $query)->where('type', 'capital_in')->sum('amount');
        $withdrawal = (float) (clone $query)->where('type', 'capital_withdrawal')->sum('amount');

        return view('owner-capital.index', [
            'transactions' => $query->latest('occurred_at')->paginate(25)->withQueryString(),
            'from' => $from,
            'to' => $to,
            'capitalIn' => $capitalIn,
            'withdrawal' => $withdrawal,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:capital_in,capital_withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'occurred_at' => 'required|date',
        ]);

        $transaction = OwnerCapitalTransaction::create([
            ...$data,
            'user_id' => auth()->id(),
        ]);
        AuditService::log('owner_capital.created', $transaction, 'Transaksi modal pemilik dicatat', ['type' => $data['type'], 'amount' => $data['amount']]);

        return back()->with('success', 'Transaksi modal pemilik berhasil dicatat.');
    }
}
