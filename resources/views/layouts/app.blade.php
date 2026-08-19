<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'POS Jamu') - POS Jamu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
@php($isAdmin = auth()->user()->isAdmin())
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">PJ</span>
            <span><b>POS Jamu</b><small>Manajemen toko sehat</small></span>
        </a>

        <div class="role-chip"><span class="status-dot"></span><span>{{ $isAdmin ? 'Akses Administrator' : 'Akses Kasir' }}</span></div>

        <nav class="sidebar-scroll">
            <p class="nav-caption">Menu Utama</p>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-glyph">D</span>Dashboard</a>
            <a class="nav-link {{ request()->routeIs('sales.create') ? 'active' : '' }}" href="{{ route('sales.create') }}"><span class="nav-glyph">K</span>Kasir</a>
            <a class="nav-link {{ request()->routeIs('sales.index') ? 'active' : '' }}" href="{{ route('sales.index') }}"><span class="nav-glyph">J</span>Penjualan</a>
            <a class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}" href="{{ route('shifts.index') }}"><span class="nav-glyph">S</span>Shift Kasir</a>
            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="nav-glyph">M</span>Pelanggan / Member</a>

            <p class="nav-caption">Persediaan</p>
            <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><span class="nav-glyph">B</span>{{ $isAdmin ? 'Pembelian' : 'Barang Masuk' }}</a>
            <a class="nav-link {{ request()->routeIs('outflows.*') ? 'active' : '' }}" href="{{ route('outflows.index') }}"><span class="nav-glyph">O</span>Pengeluaran Barang</a>
            <a class="nav-link {{ request()->routeIs('stock-card.*') ? 'active' : '' }}" href="{{ route('stock-card.index') }}"><span class="nav-glyph">ST</span>Kartu Stok</a>
            <a class="nav-link {{ request()->routeIs('batches.*') ? 'active' : '' }}" href="{{ route('batches.index') }}"><span class="nav-glyph">BT</span>Batch & Kedaluwarsa</a>
            <a class="nav-link {{ request()->routeIs('cash.*') ? 'active' : '' }}" href="{{ route('cash.index') }}"><span class="nav-glyph">KS</span>Kas & Pengeluaran</a>
            <a class="nav-link {{ request()->routeIs('opname.*') ? 'active' : '' }}" href="{{ route('opname.index') }}"><span class="nav-glyph">SO</span>{{ $isAdmin ? 'Stock Opname' : 'Lihat Stock Opname' }}</a>
            <a class="nav-link {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}" href="{{ route('sale-returns.index') }}"><span class="nav-glyph">R</span>Retur Penjualan</a>

            @if($isAdmin)
                <p class="nav-caption">Administrasi</p>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span class="nav-glyph">P</span>Master Barang</a>
                <a class="nav-link {{ request()->routeIs('master.*') ? 'active' : '' }}" href="{{ route('master.index') }}"><span class="nav-glyph">MD</span>Master Data</a>
                <a class="nav-link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}"><span class="nav-glyph">RS</span>Retur Supplier</a>
                <a class="nav-link {{ request()->routeIs('barcodes.*') ? 'active' : '' }}" href="{{ route('barcodes.index') }}"><span class="nav-glyph">BC</span>Barcode & Label</a>
                <a class="nav-link {{ request()->routeIs('promotions.*') ? 'active' : '' }}" href="{{ route('promotions.index') }}"><span class="nav-glyph">%</span>Promo & Harga</a>
                <a class="nav-link {{ request()->routeIs('supplier-debts.*') ? 'active' : '' }}" href="{{ route('supplier-debts.index') }}"><span class="nav-glyph">HS</span>Hutang Supplier</a>
                <a class="nav-link {{ request()->routeIs('owner-capital.*') ? 'active' : '' }}" href="{{ route('owner-capital.index') }}"><span class="nav-glyph">MP</span>Modal Pemilik</a>
                <a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><span class="nav-glyph">A</span>Audit Aktivitas</a>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="nav-glyph">U</span>Akun Pengguna</a>
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-glyph">G</span>Pengaturan Toko</a>

                <p class="nav-caption">Laporan</p>
                <a class="nav-link {{ request()->routeIs('reports.index', 'reports.legacy') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-glyph">LP</span>Penjualan</a>
                <a class="nav-link {{ request()->routeIs('reports.purchases') ? 'active' : '' }}" href="{{ route('reports.purchases') }}"><span class="nav-glyph">LB</span>Pembelian</a>
                <a class="nav-link {{ request()->routeIs('reports.stock') ? 'active' : '' }}" href="{{ route('reports.stock') }}"><span class="nav-glyph">LS</span>Stok</a>
                <a class="nav-link {{ request()->routeIs('reports.profit') ? 'active' : '' }}" href="{{ route('reports.profit') }}"><span class="nav-glyph">L+</span>Keuntungan</a>
                <a class="nav-link {{ request()->routeIs('reports.returns') ? 'active' : '' }}" href="{{ route('reports.returns') }}"><span class="nav-glyph">LR</span>Retur</a>
                <a class="nav-link {{ request()->routeIs('reports.cash-flow') ? 'active' : '' }}" href="{{ route('reports.cash-flow') }}"><span class="nav-glyph">AK</span>Arus Kas</a>

                <p class="nav-caption">Ekspor Cepat</p>
                <a class="nav-link" href="{{ route('reports.sales.export', request()->query()) }}"><span class="nav-glyph">EX</span>Penjualan CSV</a>
                <a class="nav-link" href="{{ route('reports.purchases.export', request()->query()) }}"><span class="nav-glyph">EX</span>Pembelian CSV</a>
                <a class="nav-link" href="{{ route('reports.stock.export', request()->query()) }}"><span class="nav-glyph">EX</span>Stok CSV</a>
                <a class="nav-link" href="{{ route('reports.profit.export', request()->query()) }}"><span class="nav-glyph">EX</span>Laba CSV</a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <b>{{ auth()->user()->name }}</b>
                <small>{{ $isAdmin ? 'Administrator' : 'Kasir' }}</small>
            </div>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-header">
            <button type="button" class="mobile-menu" aria-label="Menu">=</button>
            <div class="header-title">
                <p class="eyebrow">Selamat datang kembali, {{ auth()->user()->name }}</p>
                <h1>@yield('heading', 'Dashboard')</h1>
            </div>

            <label class="global-search" aria-label="Pencarian cepat">
                <span>Q</span>
                <input type="search" placeholder="Cari menu atau transaksi...">
            </label>

            <div class="header-actions">
                <button type="button" class="header-icon-btn" title="Notifikasi" aria-label="Notifikasi">
                    N<span class="notification-pip"></span>
                </button>
                <div class="header-user">
                    <span class="header-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><b>{{ auth()->user()->name }}</b><small>{{ $isAdmin ? 'Administrator' : 'Kasir' }}</small></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" title="Keluar" aria-label="Keluar">X</button>
                </form>
            </div>
        </header>

        <section class="app-content">
            @if(session('success'))<div class="flash-success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
