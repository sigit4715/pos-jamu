@extends('layouts.app')

@section('title', 'Manajemen Toko')
@section('heading', 'Manajemen Toko')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-black">Toko dan cabang</h2>
    <p class="mt-1 text-sm text-slate-500">Toko dan gudang memiliki stok, kas, transaksi, pembelian, dan laporan masing-masing. Salin katalog membuat barang serta kemasan yang sama dengan stok awal nol.</p>
</div>

<form method="POST" action="{{ route('stores.store') }}" class="card mb-6">
    @csrf
    <div class="mb-4 flex items-center justify-between"><h3 class="font-extrabold">Tambah Toko</h3><span class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Admin pusat</span></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div><label class="label">Kode toko *</label><input class="input" name="code" placeholder="Contoh: TOKO-CABANG-01" required></div>
        <div><label class="label">Nama toko *</label><input class="input" name="name" placeholder="Contoh: Toko Cabang Barat" required></div>
        <div><label class="label">Jenis lokasi *</label><select class="input" name="type"><option value="store">Toko / Penjualan</option><option value="warehouse">Gudang</option></select></div>
        <div><label class="label">Telepon</label><input class="input" name="phone"></div>
        <div class="xl:col-span-2"><label class="label">Alamat</label><input class="input" name="address"></div>
        <div><label class="label">Salin katalog dari</label><select class="input" name="copy_from_store_id"><option value="">Mulai tanpa katalog</option>@foreach($stores as $store)<option value="{{ $store->id }}">{{ $store->name }} (stok awal 0)</option>@endforeach</select></div>
    </div>
    <input type="hidden" name="is_active" value="1">
    <button class="btn-primary mt-5" type="submit">Tambah Toko</button>
</form>

<div class="grid gap-5 xl:grid-cols-2">
@foreach($stores as $store)
    <form method="POST" action="{{ route('stores.update', $store) }}" class="card">
        @csrf @method('PUT')
        <div class="mb-4 flex items-start justify-between gap-3">
            <div><h3 class="font-extrabold text-slate-800">{{ $store->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $store->type === 'warehouse' ? 'Gudang' : 'Toko' }} &middot; {{ $store->products_count }} barang &middot; {{ $store->users_count }} pengguna</p></div>
            @if($store->id === $activeStoreId)<span class="rounded-lg bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700">Toko aktif</span>@endif
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="label">Kode toko</label><input class="input" name="code" value="{{ $store->code }}" required></div>
            <div><label class="label">Nama toko</label><input class="input" name="name" value="{{ $store->name }}" required></div>
            <div><label class="label">Jenis lokasi</label><select class="input" name="type"><option value="store" @selected($store->type === 'store')>Toko / Penjualan</option><option value="warehouse" @selected($store->type === 'warehouse')>Gudang</option></select></div>
            <div><label class="label">Telepon</label><input class="input" name="phone" value="{{ $store->phone }}"></div>
            <div><label class="label">Status</label><select class="input" name="is_active"><option value="1" @selected($store->is_active)>Aktif</option><option value="0" @selected(!$store->is_active)>Nonaktif</option></select></div>
            <div class="md:col-span-2"><label class="label">Alamat</label><input class="input" name="address" value="{{ $store->address }}"></div>
        </div>
        <button class="btn-muted mt-4" type="submit">Simpan Perubahan</button>
    </form>
@endforeach
</div>
@endsection
