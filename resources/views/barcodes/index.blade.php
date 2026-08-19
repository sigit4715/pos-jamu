@extends('layouts.app')
@section('title', 'Barcode dan Label')
@section('heading', 'Barcode dan Label')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h2 class="text-xl font-black">Cetak barcode dan label harga</h2><p class="text-sm text-slate-500">Pilih barang untuk membuka format label siap cetak.</p></div></div>
<form class="card mb-6 flex gap-3" method="GET"><input class="input flex-1" name="q" value="{{ request('q') }}" placeholder="Cari nama, kode, atau barcode"><button class="btn-secondary" type="submit">Cari</button></form>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@forelse($products as $product)<div class="card"><div class="mb-4 flex items-start justify-between gap-3"><div><h3 class="font-extrabold">{{ $product->name }}</h3><p class="text-xs text-slate-500">{{ $product->code }}</p></div><span class="rounded-lg bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">Stok {{ $product->stock }}</span></div><div class="barcode-mini mb-4"></div><p class="text-center font-mono text-xs tracking-[0.35em]">{{ $product->barcode ?: $product->code }}</p><a class="btn-primary mt-4 block text-center" target="_blank" href="{{ route('barcodes.print', $product) }}">Buka Label Cetak</a></div>@empty<div class="card col-span-full text-center text-slate-500">Belum ada barang aktif.</div>@endforelse</div><div class="mt-5">{{ $products->links() }}</div>
<style>.barcode-mini{height:44px;background:repeating-linear-gradient(90deg,#0f172a 0 2px,transparent 2px 5px,#0f172a 5px 6px,transparent 6px 10px);border-radius:4px}</style>
@endsection
