@extends('layouts.app')

@section('title', 'Laporan Retur')
@section('heading', 'Laporan Retur')

@section('content')
    <form class="card mb-6 flex flex-wrap items-end gap-4" method="GET">
        <div><label class="label">Tanggal mulai</label><input class="input" type="date" name="from" value="{{ $from }}"></div>
        <div><label class="label">Tanggal akhir</label><input class="input" type="date" name="to" value="{{ $to }}"></div>
        <button class="btn-primary" type="submit">Tampilkan</button>
    </form>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="stat-card"><p class="text-sm text-slate-500">Retur pembelian supplier</p><p class="mt-2 text-2xl font-black text-red-600">Rp {{ number_format($purchase->sum('total'), 0, ',', '.') }}</p><small>{{ $purchase->count() }} transaksi</small></div>
        <div class="stat-card"><p class="text-sm text-slate-500">Retur penjualan pelanggan</p><p class="mt-2 text-2xl font-black text-amber-600">Rp {{ number_format($sale->sum('total'), 0, ',', '.') }}</p><small>{{ $sale->count() }} transaksi</small></div>
        <div class="stat-card"><p class="text-sm text-slate-500">Total nilai retur</p><p class="mt-2 text-2xl font-black text-slate-900">Rp {{ number_format($purchase->sum('total') + $sale->sum('total'), 0, ',', '.') }}</p><small>Periode terpilih</small></div>
    </div>

    <div class="card mt-6 overflow-x-auto p-0">
        <div class="border-b p-5"><h3 class="font-extrabold">Riwayat Retur</h3></div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nomor</th><th class="p-4">Jenis</th><th class="p-4">Referensi</th><th class="p-4">Nilai</th><th class="p-4">Alasan</th><th class="p-4">Diproses oleh</th><th class="p-4">Tanggal</th></tr></thead>
            <tbody>
                @foreach($purchase as $row)
                    <tr class="border-t"><td class="p-4 font-bold">{{ $row->number }}</td><td class="p-4"><span class="rounded-lg bg-red-100 px-2 py-1 text-xs font-bold text-red-700">Supplier</span></td><td class="p-4">{{ $row->supplier_name }}</td><td class="p-4">Rp {{ number_format($row->total, 0, ',', '.') }}</td><td class="p-4">{{ $row->reason }}</td><td class="p-4">{{ $row->user_name }}</td><td class="p-4">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td></tr>
                @endforeach
                @foreach($sale as $row)
                    <tr class="border-t"><td class="p-4 font-bold">{{ $row->number }}</td><td class="p-4"><span class="rounded-lg bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">Penjualan</span></td><td class="p-4">{{ $row->invoice_number }}</td><td class="p-4">Rp {{ number_format($row->total, 0, ',', '.') }}</td><td class="p-4">{{ $row->reason }}</td><td class="p-4">{{ $row->user_name }}</td><td class="p-4">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td></tr>
                @endforeach
                @if($purchase->isEmpty() && $sale->isEmpty())
                    <tr><td colspan="7" class="p-10 text-center text-slate-500">Belum ada retur pada periode ini.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
@endsection
