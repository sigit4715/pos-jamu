@extends('layouts.app')

@section('title', 'Retur Penjualan')
@section('heading', 'Retur Penjualan')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-black">Proses retur pelanggan</h2>
        <p class="text-sm text-slate-500">Kasir dapat memilih transaksi, mengisi jumlah barang yang dikembalikan, dan menentukan apakah stok layak dijual kembali.</p>
    </div>

    @forelse($sales as $sale)
        <form class="card mb-5" method="POST" action="{{ route('sale-returns.store') }}">
            @csrf
            <div class="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                <div>
                    <h3 class="font-bold">{{ $sale->invoice_number }}</h3>
                    <p class="text-sm text-slate-500">{{ $sale->created_at?->format('d M Y H:i') }} · Total Rp {{ number_format($sale->total, 0, ',', '.') }}</p>
                </div>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Belum selesai diretur</span>
            </div>

            <div class="mt-3 space-y-2">
                @foreach($sale->items as $item)
                    @php($remaining = max(0, $item->quantity - $item->returned_quantity))
                    @if($remaining > 0)
                        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 p-3">
                            <input type="checkbox" name="sale_item_id[]" value="{{ $item->id }}" class="mt-1">
                            <div class="min-w-[180px] flex-1">
                                <b>{{ $item->product_name }}</b>
                                <small class="block text-slate-500">Terjual {{ $item->quantity }} · Sisa retur {{ $remaining }} · Rp {{ number_format($item->price, 0, ',', '.') }}/item</small>
                            </div>
                            <label class="text-xs text-slate-500">Jumlah
                                <input class="input mt-1 w-24" name="quantity[{{ $item->id }}]" type="number" min="0" max="{{ $remaining }}" value="0">
                            </label>
                            <label class="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="restock[]" value="{{ $item->id }}" checked>
                                Stok kembali
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3">
                <label class="min-w-[260px] flex-1 text-sm font-semibold">Alasan retur
                    <input class="input mt-1" name="reason" placeholder="Contoh: barang rusak atau salah kirim" required maxlength="1000">
                </label>
                <button class="btn-primary" type="submit">Proses Retur Penjualan</button>
            </div>
        </form>
    @empty
        <div class="card mb-6 text-center text-slate-500">Belum ada transaksi yang masih memiliki barang untuk diretur.</div>
    @endforelse

    <div class="card overflow-x-auto p-0">
        <div class="border-b p-5"><h3 class="font-extrabold">Riwayat Retur Penjualan</h3></div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nomor</th><th class="p-4">Invoice</th><th class="p-4">Nilai</th><th class="p-4">Alasan</th><th class="p-4">Diproses oleh</th><th class="p-4">Tanggal</th></tr></thead>
            <tbody>
                @forelse($returns as $return)
                    <tr class="border-t"><td class="p-4 font-bold">{{ $return->number }}</td><td class="p-4">{{ $return->invoice_number }}</td><td class="p-4">Rp {{ number_format($return->total, 0, ',', '.') }}</td><td class="p-4">{{ $return->reason }}</td><td class="p-4">{{ $return->user_name }}</td><td class="p-4">{{ \Illuminate\Support\Carbon::parse($return->created_at)->format('d M Y H:i') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada retur penjualan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
