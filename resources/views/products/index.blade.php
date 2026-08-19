@extends('layouts.app')

@section('title', 'Master Barang')
@section('heading', 'Master Barang')

@section('content')
<div class="mb-5 flex flex-wrap justify-between gap-3">
    <form class="flex gap-2"><input class="input w-72" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, barcode"><button class="btn-muted">Cari</button></form>
    <a class="btn-primary" href="{{ route('products.create') }}">Tambah Barang</a>
</div>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Barang</th><th class="p-4">Kategori</th><th class="p-4">Harga satuan</th><th class="p-4">Kemasan & harga</th><th class="p-4">Stok / Min.</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
        <tbody>
            @forelse($products as $product)
                <tr class="border-t border-slate-100 align-top">
                    <td class="p-4"><b>{{ $product->name }}</b><small class="mt-1 block text-slate-500">{{ $product->code }} @if($product->barcode)&middot; {{ $product->barcode }} @endif</small></td>
                    <td class="p-4">{{ $product->category?->name ?? '-' }}</td>
                    <td class="p-4"><b>Rp {{ number_format($product->price, 0, ',', '.') }}</b><small class="mt-1 block text-slate-500">per {{ $product->unit }}</small></td>
                    <td class="p-4">
                        @forelse($product->packagings as $packaging)
                            <div class="mb-1 text-xs {{ $packaging->is_active ? 'text-slate-700' : 'text-slate-400 line-through' }}"><b>{{ $packaging->name }}</b> = {{ $packaging->conversion_quantity }} {{ $product->unit }} &middot; Rp {{ number_format($packaging->price, 0, ',', '.') }}</div>
                        @empty
                            <span class="text-xs text-slate-400">Belum ada kemasan tambahan</span>
                        @endforelse
                    </td>
                    <td class="p-4"><span class="font-bold {{ $product->stock <= $product->minimum_stock ? 'text-amber-600' : 'text-emerald-600' }}">{{ $product->stock }}</span> <small>/ {{ $product->minimum_stock }} {{ $product->unit }}</small></td>
                    <td class="p-4">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td class="p-4 text-right"><a class="font-bold text-emerald-600" href="{{ route('products.edit', $product) }}">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">Belum ada master barang.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $products->links() }}</div>
@endsection
