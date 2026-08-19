<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\AuditService;
use App\Services\StoreContext;

class ShiftController extends Controller
{
    public function index()
    {
        $storeId = $this->storeId();
        $current = CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->latest()->first();
        $query = CashierShift::where('store_id', $storeId)->with('user')->latest('opened_at');
        if (! auth()->user()->hasPermission('shifts.view_all')) $query->where('user_id', auth()->id());

        return view('shifts.index', [
            'current' => $current,
            'expected' => $current ? $this->expectedCash($current) : null,
            'shifts' => $query->paginate(15),
        ]);
    }

    public function open(Request $request)
    {
        $data = $request->validate(['opening_cash' => 'required|numeric|min:0', 'notes' => 'nullable|string|max:500']);
        $storeId = $this->storeId();
        if (CashierShift::where('store_id', $storeId)->where('user_id', auth()->id())->where('status', 'open')->exists()) {
            throw ValidationException::withMessages(['opening_cash' => 'Masih ada shift yang terbuka. Tutup shift sebelumnya terlebih dahulu.']);
        }
        CashierShift::create(['store_id' => $storeId, 'user_id' => auth()->id(), 'opened_at' => now(), 'opening_cash' => $data['opening_cash'], 'status' => 'open', 'notes' => $data['notes'] ?? null]);
        AuditService::log('shift.opened', null, 'Shift kasir dibuka', ['opening_cash' => $data['opening_cash']]);
        return back()->with('success', 'Shift kasir berhasil dibuka.');
    }

    public function close(Request $request)
    {
        $data = $request->validate(['closing_cash' => 'required|numeric|min:0', 'notes' => 'nullable|string|max:500']);
        $shift = CashierShift::where('store_id', $this->storeId())->where('user_id', auth()->id())->where('status', 'open')->latest()->first();
        if (!$shift) throw ValidationException::withMessages(['closing_cash' => 'Tidak ada shift kasir yang sedang terbuka.']);
        $expected = $this->expectedCash($shift);
        $shift->update(['closed_at' => now(), 'expected_cash' => $expected, 'closing_cash' => $data['closing_cash'], 'difference' => $data['closing_cash'] - $expected, 'status' => 'closed', 'notes' => $data['notes'] ?? $shift->notes]);
        AuditService::log('shift.closed', $shift, 'Shift kasir ditutup', ['expected_cash' => $expected, 'closing_cash' => $data['closing_cash'], 'difference' => $data['closing_cash'] - $expected]);
        return back()->with('success', 'Shift kasir berhasil ditutup.');
    }

    private function expectedCash(CashierShift $shift): float
    {
        $sales = (float) Sale::where('shift_id', $shift->id)->where('payment_method', 'cash')->sum('total');
        $returns = (float) DB::table('sale_returns as r')->join('sales as s', 's.id', '=', 'r.sale_id')->where('s.shift_id', $shift->id)->sum('r.total');
        $cash = (float) DB::table('cash_transactions')->where('shift_id', $shift->id)->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as total")->value('total');
        return (float) $shift->opening_cash + $sales - $returns + $cash;
    }
    private function storeId(): int { return app(StoreContext::class)->id(); }
}
