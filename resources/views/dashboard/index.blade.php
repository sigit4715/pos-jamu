@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
@php
    $chartMax = max(1, (float) $chart->max('total'), (float) $chart->max('profit'));
    $chartLastIndex = max(1, $chart->count() - 1);
    $salesPoints = $chart->values()->map(function ($point, $index) use ($chartLastIndex, $chartMax) {
        return round(20 + ($index * (660 / $chartLastIndex)), 1).','.round(176 - (($point['total'] / $chartMax) * 144), 1);
    })->implode(' ');
    $profitPoints = $chart->values()->map(function ($point, $index) use ($chartLastIndex, $chartMax) {
        return round(20 + ($index * (660 / $chartLastIndex)), 1).','.round(176 - (($point['profit'] / $chartMax) * 144), 1);
    })->implode(' ');
    $salesArea = $salesPoints.' 680,176 20,176';

    $paymentRows = $paymentSummary->take(4)->values();
    $categoryRows = $categorySummary->take(4)->values();
    $paymentColors = ['#28b56f', '#3386db', '#8568dc', '#f4a340'];
    $categoryColors = ['#28b56f', '#f3b241', '#697ce4', '#dd6dba'];
    $buildStops = function ($rows, $colors) {
        if ($rows->isEmpty() || $rows->sum('total') <= 0) return ['#e8edf3 0% 100%'];
        $total = (float) $rows->sum('total');
        $position = 0;
        $stops = [];
        foreach ($rows as $index => $row) {
            $next = $index === $rows->count() - 1 ? 100 : min(100, $position + (($row['total'] / $total) * 100));
            $stops[] = $colors[$index].' '.round($position, 2).'% '.round($next, 2).'%';
            $position = $next;
        }
        return $stops;
    };
    $paymentStops = $buildStops($paymentRows, $paymentColors);
    $categoryStops = $buildStops($categoryRows, $categoryColors);
@endphp

<div class="dashboard-toolbar">
    <div class="dashboard-welcome">
        <h2>Ringkasan toko hari ini</h2>
        <p>Pantau penjualan, persediaan, dan aktivitas kasir dalam satu layar.</p>
    </div>
    <div class="dashboard-filters">
        <span class="filter-pill">Tgl {{ now()->translatedFormat('d M Y') }}</span>
        <span class="filter-pill">{{ auth()->user()->isAdmin() ? 'Semua Aktivitas' : 'Aktivitas Saya' }}</span>
        <a href="{{ route('sales.create') }}" class="btn-primary">+ Transaksi Baru</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="metric-card metric-green">
        <span class="metric-icon">Rp</span>
        <p class="metric-label">Omzet Hari Ini</p>
        <p class="metric-value">Rp {{ number_format($salesToday, 0, ',', '.') }}</p>
        <p class="metric-note">Penjualan tercatat hari ini</p>
        <svg class="metric-spark" viewBox="0 0 240 32" preserveAspectRatio="none" aria-hidden="true"><path d="M0 28 L25 24 L50 25 L75 16 L100 22 L125 12 L150 17 L180 7 L210 15 L240 5" fill="none" stroke="#28b56f" stroke-width="2.2"/><path d="M0 28 L25 24 L50 25 L75 16 L100 22 L125 12 L150 17 L180 7 L210 15 L240 5 L240 32 L0 32Z" fill="rgba(40,181,111,.10)"/></svg>
    </article>
    <article class="metric-card metric-blue">
        <span class="metric-icon">TR</span>
        <p class="metric-label">Transaksi</p>
        <p class="metric-value">{{ number_format($transactionsToday, 0, ',', '.') }}</p>
        <p class="metric-note">Transaksi berhasil diproses</p>
        <svg class="metric-spark" viewBox="0 0 240 32" preserveAspectRatio="none" aria-hidden="true"><path d="M0 27 L26 22 L54 24 L80 13 L106 18 L132 10 L158 20 L184 13 L212 18 L240 8" fill="none" stroke="#3386db" stroke-width="2.2"/><path d="M0 27 L26 22 L54 24 L80 13 L106 18 L132 10 L158 20 L184 13 L212 18 L240 8 L240 32 L0 32Z" fill="rgba(51,134,219,.10)"/></svg>
    </article>
    <article class="metric-card metric-purple">
        <span class="metric-icon">LB</span>
        <p class="metric-label">Laba Kotor</p>
        <p class="metric-value">Rp {{ number_format($grossProfitToday, 0, ',', '.') }}</p>
        <p class="metric-note">Estimasi dari transaksi hari ini</p>
        <svg class="metric-spark" viewBox="0 0 240 32" preserveAspectRatio="none" aria-hidden="true"><path d="M0 28 L25 26 L50 19 L75 23 L100 14 L125 20 L150 9 L175 16 L205 10 L240 6" fill="none" stroke="#8568dc" stroke-width="2.2"/><path d="M0 28 L25 26 L50 19 L75 23 L100 14 L125 20 L150 9 L175 16 L205 10 L240 6 L240 32 L0 32Z" fill="rgba(133,104,220,.10)"/></svg>
    </article>
    <article class="metric-card metric-orange">
        <span class="metric-icon">BR</span>
        <p class="metric-label">Produk Terjual</p>
        <p class="metric-value">{{ number_format($soldProductsToday, 0, ',', '.') }}</p>
        <p class="metric-note">{{ $productCount }} produk aktif tersedia</p>
        <svg class="metric-spark" viewBox="0 0 240 32" preserveAspectRatio="none" aria-hidden="true"><path d="M0 28 L25 20 L50 24 L75 16 L100 19 L125 8 L150 15 L175 10 L205 13 L240 5" fill="none" stroke="#f4a340" stroke-width="2.2"/><path d="M0 28 L25 20 L50 24 L75 16 L100 19 L125 8 L150 15 L175 10 L205 13 L240 5 L240 32 L0 32Z" fill="rgba(244,163,64,.10)"/></svg>
    </article>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-12">
    <section class="card xl:col-span-7">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h3 class="panel-title">Grafik Penjualan</h3><p class="panel-subtitle">Pergerakan omzet dan laba kotor 7 hari terakhir</p></div>
            <div class="chart-legend"><span><i class="legend-dot bg-emerald-500"></i> Omzet</span><span><i class="legend-dot bg-violet-500"></i> Laba kotor</span></div>
        </div>
        <svg class="line-chart" viewBox="0 0 700 220" role="img" aria-label="Grafik penjualan 7 hari terakhir">
            @foreach([32, 75, 118, 161] as $line)<line class="chart-grid" x1="20" x2="680" y1="{{ $line }}" y2="{{ $line }}"/><text class="chart-label" x="0" y="{{ $line + 3 }}">Rp</text>@endforeach
            <polygon points="{{ $salesArea }}" fill="rgba(40,181,111,.09)"></polygon>
            <polyline points="{{ $profitPoints }}" fill="none" stroke="#8568dc" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"></polyline>
            <polyline points="{{ $salesPoints }}" fill="none" stroke="#28b56f" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"></polyline>
            @foreach($chart->values() as $index => $point)
                @php($x = round(20 + ($index * (660 / $chartLastIndex)), 1))
                @php($saleY = round(176 - (($point['total'] / $chartMax) * 144), 1))
                @php($profitY = round(176 - (($point['profit'] / $chartMax) * 144), 1))
                <circle cx="{{ $x }}" cy="{{ $saleY }}" r="3.6" fill="#fff" stroke="#28b56f" stroke-width="2.2"></circle>
                <circle cx="{{ $x }}" cy="{{ $profitY }}" r="2.7" fill="#fff" stroke="#8568dc" stroke-width="1.8"></circle>
                <text class="chart-label" text-anchor="middle" x="{{ $x }}" y="204">{{ $point['label'] }}</text>
            @endforeach
        </svg>
        <div class="mt-1 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-[10px] text-slate-500">
            <span>Total omzet 7 hari: <b class="text-slate-700">Rp {{ number_format($chart->sum('total'), 0, ',', '.') }}</b></span>
            <a href="{{ route('reports.index') }}" class="panel-link">Buka laporan penjualan</a>
        </div>
    </section>

    <section class="card xl:col-span-3">
        <div><h3 class="panel-title">Ringkasan Keuangan</h3><p class="panel-subtitle">Posisi bulan berjalan</p></div>
        <div class="mt-2">
            <div class="summary-row"><span class="summary-icon bg-emerald-50 text-emerald-600">K</span><span class="text-[11px] font-semibold text-slate-500">Kas bulan ini</span><b>Rp {{ number_format($cashBalance, 0, ',', '.') }}</b></div>
            <div class="summary-row"><span class="summary-icon bg-sky-50 text-sky-600">S</span><span class="text-[11px] font-semibold text-slate-500">Nilai stok</span><b>Rp {{ number_format($stockValue, 0, ',', '.') }}</b></div>
            @if(auth()->user()->isAdmin())
                <div class="summary-row"><span class="summary-icon bg-violet-50 text-violet-600">M</span><span class="text-[11px] font-semibold text-slate-500">Modal pemilik</span><b>Rp {{ number_format($ownerCapital, 0, ',', '.') }}</b></div>
                <div class="summary-row"><span class="summary-icon bg-amber-50 text-amber-600">H</span><span class="text-[11px] font-semibold text-slate-500">Hutang supplier</span><b>Rp {{ number_format($supplierDebt, 0, ',', '.') }}</b></div>
            @else
                <div class="summary-row"><span class="summary-icon bg-violet-50 text-violet-600">SH</span><span class="text-[11px] font-semibold text-slate-500">Status shift</span><b>{{ $currentShift ? 'Aktif' : 'Belum dibuka' }}</b></div>
            @endif
            <div class="summary-row"><span class="summary-icon bg-rose-50 text-rose-600">E</span><span class="text-[11px] font-semibold text-slate-500">Batch segera habis</span><b>{{ $expiryAlerts->count() }} batch</b></div>
        </div>
    </section>

    <section class="card xl:col-span-2">
        <div class="flex items-start justify-between gap-2"><div><h3 class="panel-title">Stok Menipis</h3><p class="panel-subtitle">Perlu diperiksa</p></div><span class="rounded-md bg-rose-50 px-1.5 py-1 text-[9px] font-black text-rose-600">{{ $lowStock->count() }}</span></div>
        <div class="stock-list">
            @forelse($lowStock->take(4) as $product)
                <a href="{{ auth()->user()->isAdmin() ? route('products.edit', $product) : route('stock-card.index') }}" class="stock-item">
                    <span><b>{{ $product->name }}</b><small>Minimum {{ $product->minimum_stock }} {{ $product->unit }}</small></span>
                    <span class="stock-amount">{{ $product->stock }}</span>
                </a>
            @empty
                <p class="empty-state">Semua stok aman.</p>
            @endforelse
        </div>
        <a href="{{ route('stock-card.index') }}" class="panel-link mt-3 block text-center">Lihat semua stok</a>
    </section>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-12">
    <section class="card xl:col-span-3">
        <div><h3 class="panel-title">Penjualan per Metode</h3><p class="panel-subtitle">Omzet bulan berjalan</p></div>
        <div class="mt-5 flex items-center gap-5">
            <div class="donut" style="background:conic-gradient({{ implode(', ', $paymentStops) }})">
                <span class="donut-center"><b>{{ $paymentRows->count() }}</b><small>metode</small></span>
            </div>
            <div class="donut-legend min-w-0 flex-1">
                @forelse($paymentRows as $index => $payment)
                    <div class="donut-legend-row"><span class="legend-name truncate"><i class="donut-color" style="background:{{ $paymentColors[$index] }}"></i>{{ $payment['name'] }}</span><b>{{ $paymentSummary->sum('total') > 0 ? number_format(($payment['total'] / $paymentSummary->sum('total')) * 100, 0) : 0 }}%</b></div>
                @empty
                    <p class="text-[11px] text-slate-400">Belum ada penjualan.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card xl:col-span-3">
        <div><h3 class="panel-title">Penjualan per Kategori</h3><p class="panel-subtitle">Kategori barang terjual</p></div>
        <div class="mt-5 flex items-center gap-5">
            <div class="donut" style="background:conic-gradient({{ implode(', ', $categoryStops) }})">
                <span class="donut-center"><b>{{ $categoryRows->count() }}</b><small>kategori</small></span>
            </div>
            <div class="donut-legend min-w-0 flex-1">
                @forelse($categoryRows as $index => $category)
                    <div class="donut-legend-row"><span class="legend-name truncate"><i class="donut-color" style="background:{{ $categoryColors[$index] }}"></i>{{ $category['name'] }}</span><b>{{ $categorySummary->sum('total') > 0 ? number_format(($category['total'] / $categorySummary->sum('total')) * 100, 0) : 0 }}%</b></div>
                @empty
                    <p class="text-[11px] text-slate-400">Belum ada penjualan.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card xl:col-span-6">
        <div class="flex items-start justify-between gap-3">
            <div><h3 class="panel-title">Transaksi Terakhir</h3><p class="panel-subtitle">Aktivitas penjualan paling baru</p></div>
            <a href="{{ route('sales.index') }}" class="panel-link">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="dashboard-table">
                <thead><tr><th>No. Faktur</th><th>Pelanggan</th><th>Metode</th><th>Total</th><th>Waktu</th></tr></thead>
                <tbody>
                @forelse($recentSales as $sale)
                    <tr>
                        <td><a href="{{ route('sales.receipt', $sale) }}">{{ $sale->invoice_number }}</a></td>
                        <td>{{ $sale->customer_name ?: 'Umum' }}</td>
                        <td><span class="payment-badge">{{ ucfirst($sale->payment_method ?: 'Tunai') }}</span></td>
                        <td class="font-bold text-emerald-600">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                        <td>{{ $sale->created_at->format('d M H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada transaksi tercatat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@if($expiryAlerts->isNotEmpty() || $topProducts->isNotEmpty())
<div class="mt-5 grid gap-5 lg:grid-cols-2">
    <section class="card">
        <div class="flex items-center justify-between"><div><h3 class="panel-title">Batch Kedaluwarsa Terdekat</h3><p class="panel-subtitle">Periksa kualitas barang sebelum tanggal habis</p></div><a href="{{ route('batches.index') }}" class="panel-link">Kelola batch</a></div>
        <div class="stock-list">
            @forelse($expiryAlerts->take(3) as $batch)
                <div class="stock-item"><span><b>{{ $batch->product->name }}</b><small>Batch {{ $batch->batch_number }}</small></span><span class="stock-amount">{{ $batch->expires_at->format('d M Y') }}</span></div>
            @empty
                <p class="empty-state">Tidak ada batch yang segera kedaluwarsa.</p>
            @endforelse
        </div>
    </section>
    <section class="card">
        <div class="flex items-center justify-between"><div><h3 class="panel-title">Produk Terlaris</h3><p class="panel-subtitle">Akumulasi penjualan bulan ini</p></div><a href="{{ route('reports.index') }}" class="panel-link">Buka laporan</a></div>
        <div class="stock-list">
            @forelse($topProducts->take(3) as $product)
                <div class="stock-item"><span><b>{{ $product->product_name }}</b><small>Produk favorit pelanggan</small></span><span class="stock-amount">{{ $product->qty }} terjual</span></div>
            @empty
                <p class="empty-state">Belum ada data produk terlaris.</p>
            @endforelse
        </div>
    </section>
</div>
@endif
@endsection
