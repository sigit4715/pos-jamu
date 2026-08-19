@extends('layouts.app')

@section('title', 'Stock Opname')
@section('heading', 'Stock Opname')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-xl font-black">Stock Opname</h2>
        <p class="mt-1 text-sm text-slate-500">Hitung stok fisik per barang, kemudian telusuri barang masuk dan keluar tanpa memuat seluruh katalog sekaligus.</p>
    </div>
    <a class="btn-muted" href="{{ route('stock-card.index') }}">Buka Kartu Stok</a>
</div>

<form method="GET" class="card mb-5">
    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_160px_auto_auto]">
        <div>
            <label class="label" for="stock-search">Cari barang</label>
            <input class="input" id="stock-search" name="search" value="{{ $search }}" placeholder="Nama, kode, atau barcode barang">
        </div>
        <div>
            <label class="label" for="per-page">Tampil per halaman</label>
            <select class="input" id="per-page" name="per_page">
                @foreach([10, 20, 50, 100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} barang</option>@endforeach
            </select>
        </div>
        <button class="btn-primary self-end" type="submit">Cari</button>
        <a class="btn-muted self-end" href="{{ route('opname.index') }}">Reset</a>
    </div>
</form>

@if($canEdit)
    <form method="POST" action="{{ route('opname.store') }}" class="card overflow-hidden p-0">
        @csrf
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-extrabold text-slate-800">Daftar stok fisik</h3>
            <p class="mt-1 text-xs text-slate-500">Hanya barang pada halaman ini yang disimpan saat tombol opname ditekan. Gunakan pencarian atau halaman berikutnya untuk barang lain.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="p-4">Barang</th>
                        <th class="p-4 text-center">Stok Sistem</th>
                        <th class="p-4 text-center">Masuk Pembelian</th>
                        <th class="p-4 text-center">Keluar Penjualan</th>
                        <th class="p-4 text-center">Pengeluaran Lain</th>
                        <th class="p-4">Riwayat</th>
                        <th class="min-w-[150px] p-4">Stok Fisik</th>
                        <th class="min-w-[220px] p-4">Alasan Selisih</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr class="border-t border-slate-100 align-middle">
                        <td class="p-4">
                            <b class="block text-slate-800">{{ $product->name }}</b>
                            <small class="mt-0.5 block text-xs text-slate-400">{{ $product->code }}{{ $product->unit ? ' · '.$product->unit : '' }}</small>
                        </td>
                        <td class="p-4 text-center font-bold text-slate-700">{{ $product->stock }}</td>
                        <td class="p-4 text-center">
                            <a class="inline-flex rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'purchase']) }}">+{{ number_format($product->purchase_in, 0, ',', '.') }}</a>
                        </td>
                        <td class="p-4 text-center">
                            <a class="inline-flex rounded-md bg-rose-50 px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'sale']) }}">-{{ number_format($product->sale_out, 0, ',', '.') }}</a>
                        </td>
                        <td class="p-4 text-center">
                            <a class="inline-flex rounded-md bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 hover:bg-amber-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'other_out']) }}">-{{ number_format($product->other_out, 0, ',', '.') }}</a>
                        </td>
                        <td class="p-4"><a class="panel-link whitespace-nowrap" href="{{ route('stock-card.index', ['product_id' => $product->id]) }}">Lihat lengkap</a></td>
                        <td class="p-4"><input class="input min-w-[120px]" name="physical[{{ $product->id }}]" type="number" min="0" value="{{ $product->stock }}"></td>
                        <td class="p-4"><input class="input min-w-[190px]" name="reason[{{ $product->id }}]" placeholder="Contoh: barang rusak"></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-10 text-center text-slate-500">Barang tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($products->isNotEmpty())
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
                <p class="text-xs text-slate-500">Menampilkan {{ $products->firstItem() }}-{{ $products->lastItem() }} dari {{ $products->total() }} barang.</p>
                <button class="btn-primary" type="submit">Simpan Stock Opname Halaman Ini</button>
            </div>
        @endif
    </form>
@else
    <div class="card overflow-hidden p-0">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-extrabold text-slate-800">Daftar stok dan riwayat pergerakan</h3>
            <p class="mt-1 text-xs text-slate-500">Kasir dapat mengecek stok, pembelian masuk, penjualan keluar, dan riwayat lengkap per barang.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr><th class="p-4">Barang</th><th class="p-4 text-center">Stok Sistem</th><th class="p-4 text-center">Masuk Pembelian</th><th class="p-4 text-center">Keluar Penjualan</th><th class="p-4 text-center">Pengeluaran Lain</th><th class="p-4">Riwayat</th></tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr class="border-t border-slate-100">
                        <td class="p-4"><b class="block text-slate-800">{{ $product->name }}</b><small class="mt-0.5 block text-xs text-slate-400">{{ $product->code }}{{ $product->unit ? ' · '.$product->unit : '' }}</small></td>
                        <td class="p-4 text-center font-bold text-slate-700">{{ $product->stock }}</td>
                        <td class="p-4 text-center"><a class="inline-flex rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'purchase']) }}">+{{ number_format($product->purchase_in, 0, ',', '.') }}</a></td>
                        <td class="p-4 text-center"><a class="inline-flex rounded-md bg-rose-50 px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'sale']) }}">-{{ number_format($product->sale_out, 0, ',', '.') }}</a></td>
                        <td class="p-4 text-center"><a class="inline-flex rounded-md bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 hover:bg-amber-100" href="{{ route('stock-card.index', ['product_id' => $product->id, 'type' => 'other_out']) }}">-{{ number_format($product->other_out, 0, ',', '.') }}</a></td>
                        <td class="p-4"><a class="panel-link whitespace-nowrap" href="{{ route('stock-card.index', ['product_id' => $product->id]) }}">Lihat lengkap</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-slate-500">Barang tidak ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700">Mode lihat saja untuk kasir. Penyesuaian stock opname dilakukan admin.</div>
@endif

@if($products->hasPages())
    <div class="mt-5">{{ $products->links() }}</div>
@endif
@endsection
