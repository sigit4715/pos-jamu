@extends('layouts.app')

@section('title', 'Notifikasi Aktivitas')
@section('heading', 'Notifikasi Aktivitas')

@section('content')
    <div class="dashboard-toolbar">
        <div class="dashboard-welcome">
            <h2>Aktivitas lokasi hari ini</h2>
            <p>Lihat jumlah transaksi dan perpindahan stok pada setiap toko maupun gudang.</p>
        </div>
        <span class="filter-pill">{{ now()->translatedFormat('d M Y') }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach($stores as $location)
            @php($store = $location['store'])
            <article class="card">
                <div class="flex items-start justify-between gap-3">
                    <div><h3 class="panel-title">{{ $store->name }}</h3><p class="panel-subtitle">{{ $store->type === 'warehouse' ? 'Gudang' : 'Toko' }}</p></div>
                    <form method="POST" action="{{ route('stores.switch') }}">@csrf<input type="hidden" name="store_id" value="{{ $store->id }}"><button class="panel-link" type="submit">Buka lokasi</button></form>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                    <div><p class="text-[11px] text-slate-400">Penjualan</p><b class="mt-1 block text-xl text-slate-800">{{ $location['sales_count'] }}</b><small class="text-[10px] text-emerald-600">Rp {{ number_format($location['sales_total'], 0, ',', '.') }}</small></div>
                    <div><p class="text-[11px] text-slate-400">Transfer stok</p><b class="mt-1 block text-xl text-slate-800">{{ $location['transfers_in'] + $location['transfers_out'] }}</b><small class="text-[10px] text-slate-500">Masuk {{ $location['transfers_in'] }} · Keluar {{ $location['transfers_out'] }}</small></div>
                </div>
            </article>
        @endforeach
    </div>

    <section class="card mt-5 overflow-x-auto p-0">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="panel-title">Detail aktivitas</h3><p class="panel-subtitle">Transaksi dan transfer stok yang tercatat hari ini.</p></div><span class="filter-pill">{{ $activities->count() }} aktivitas</span></div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Waktu</th><th class="p-4">Lokasi</th><th class="p-4">Aktivitas</th><th class="p-4">Keterangan</th><th class="p-4 text-right">Nilai</th></tr></thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr class="border-t border-slate-100"><td class="p-4 text-slate-500">{{ $activity['at']->format('H:i') }}</td><td class="p-4 font-semibold text-slate-700">{{ $activity['location'] }}</td><td class="p-4"><span class="payment-badge">{{ $activity['type'] }}</span></td><td class="p-4 text-slate-600">{{ $activity['description'] }}</td><td class="p-4 text-right font-bold text-emerald-600">{{ $activity['amount'] !== null ? 'Rp '.number_format($activity['amount'], 0, ',', '.') : '-' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada transaksi atau transfer stok hari ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
