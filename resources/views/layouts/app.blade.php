<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'POS Jamu') - POS Jamu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
@php($currentUser = auth()->user()->loadMissing(['accessRole.permissions', 'permissionOverrides.permission']))
@php($storeContext = app(\App\Services\StoreContext::class))
@php($activeStore = $storeContext->store())
@php($canSwitchStore = $currentUser->hasPermission('stores.switch'))
@php($availableStores = $canSwitchStore ? $storeContext->stores() : collect())
@php($notificationStoreIds = $currentUser->hasPermission('dashboard.view_all') ? $storeContext->stores()->pluck('id') : collect([$activeStore->id]))
@php($notificationCount = \App\Models\Sale::whereIn('store_id', $notificationStoreIds)->whereDate('created_at', today())->count() + \App\Models\StockTransfer::where(fn ($query) => $query->whereIn('source_store_id', $notificationStoreIds)->orWhereIn('destination_store_id', $notificationStoreIds))->whereDate('transferred_at', today())->count())
@php($menuPreviewRoleCode = $currentUser->isAdmin() ? session('menu_preview_role', 'admin') : null)
@php($menuPreviewRole = $menuPreviewRoleCode ? \App\Models\AccessRole::with('permissions')->where('code', $menuPreviewRoleCode)->first() : null)
@php($displayRoleLabel = $menuPreviewRole && $menuPreviewRole->code !== 'admin' ? 'Pratinjau '.$menuPreviewRole->name : $currentUser->roleLabel())
@php($sectionOrder = ['Menu Utama' => 10, 'Persediaan' => 20, 'Administrasi' => 30, 'Laporan' => 40, 'Ekspor Cepat' => 50])
@php($menuGroups = \App\Models\MenuItem::with('permission')->where('is_active', true)->orderBy('sort_order')->get()->filter(fn ($menu) => $menuPreviewRole ? $menuPreviewRole->permissions->contains('code', $menu->permission->code) : $currentUser->hasPermission($menu->permission->code))->groupBy('section')->sortBy(fn ($menus, $section) => $sectionOrder[$section] ?? 99))
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">@include('components.icon', ['name' => 'leaf', 'class' => 'h-5 w-5'])</span>
            <span><b>POS Jamu</b><small>Manajemen toko sehat</small></span>
        </a>

        <div class="role-chip"><span class="status-dot"></span><span>{{ $displayRoleLabel }}</span></div>

        <nav class="sidebar-scroll">
            @foreach($menuGroups as $section => $menus)
                <p class="nav-caption">{{ $section }}</p>
                @foreach($menus as $menu)
                    <a class="nav-link {{ request()->routeIs($menu->route_pattern ?: $menu->route_name) ? 'active' : '' }}" href="{{ route($menu->route_name) }}">
                        <span class="nav-glyph">@include('components.icon', ['name' => $menu->icon])</span>{{ $menu->name }}
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="sidebar-footer-block">
            <div class="sidebar-footer">
                <span class="avatar">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <b>{{ $currentUser->name }}</b>
                    <small>{{ $displayRoleLabel }}</small>
                </div>
            </div>
            @if($currentUser->isAdmin())
                <form method="POST" action="{{ route('admin.menu-preview.update') }}" class="preview-role-form">
                    @csrf
                    <label for="menu-preview-role">Tampilan menu</label>
                    <select id="menu-preview-role" name="role" onchange="this.form.submit()">
                        <option value="admin" @selected($menuPreviewRoleCode === 'admin')>Administrator</option>
                        <option value="kasir" @selected($menuPreviewRoleCode === 'kasir')>Kasir</option>
                        <option value="gudang" @selected($menuPreviewRoleCode === 'gudang')>Petugas Gudang</option>
                    </select>
                </form>
            @endif
        </div>
    </aside>

    <main class="app-main">
        <header class="app-header">
            <button type="button" class="mobile-menu" aria-label="Menu">@include('components.icon', ['name' => 'menu'])</button>
            <div class="header-title">
                <p class="eyebrow">Selamat datang kembali, {{ $currentUser->name }}</p>
                <h1>@yield('heading', 'Dashboard')</h1>
            </div>

            <label class="global-search" aria-label="Pencarian cepat">
                @include('components.icon', ['name' => 'search'])
                <input type="search" placeholder="Cari menu atau transaksi...">
            </label>

            <div class="header-actions">
                @if($canSwitchStore)
                    <form method="POST" action="{{ route('stores.switch') }}" class="store-switch-form">
                        @csrf
                        <label class="sr-only" for="active-store">Lokasi aktif</label>
                        <select id="active-store" name="store_id" class="store-switch" onchange="this.form.submit()">
                            @foreach($availableStores as $store)
                                <option value="{{ $store->id }}" @selected($store->id === $activeStore->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="store-label">{{ $activeStore->name }}</span>
                @endif
                <a href="{{ route('notifications.index') }}" class="header-icon-btn" title="Aktivitas lokasi" aria-label="Aktivitas lokasi">
                    @include('components.icon', ['name' => 'bell'])
                    @if($notificationCount > 0)<span class="notification-count">{{ min($notificationCount, 99) }}</span>@endif
                </a>
                <div class="header-user">
                    <span class="header-avatar">{{ strtoupper(substr($currentUser->name, 0, 1)) }}</span>
                    <div><b>{{ $currentUser->name }}</b><small>{{ $currentUser->roleLabel() }}</small></div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" title="Keluar" aria-label="Keluar">@include('components.icon', ['name' => 'logout'])</button>
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
