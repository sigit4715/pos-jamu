@extends('layouts.app')

@section('title', 'Retur Supplier')
@section('heading', 'Retur Pembelian Supplier')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-black">Proses retur ke supplier</h2>
        <p class="text-sm text-slate-500">Stok berkurang dan riwayat retur tetap tersimpan.</p>
    </div>

    @forelse($purchases as $purchase)
        <form class="card mb-5" method="POST" action="{{ route('purchase-returns.store') }}">
            @csrf
            <div class="border-b pb-3"><h3 class="font-bold">{{ $purchase->number }} · {{ $purchase->supplier->name }}</h3></div>
            <div class="mt-3 space-y-2">
                @foreach($purchase->items as $item)
                    @if($item->quantity > $item->returned_quantity)
                        @php($remaining = $item->quantity - $item->returned_quantity)
                        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-slate-50 p-3">
                            <input type="checkbox" name="purchase_item_id[]" value="{{ $item->id }}">
                            <div class="min-w-[180px] flex-1"><b>{{ $item->product_name }}</b><small class="block text-slate-500">Sisa retur {{ $remaining }} · Rp {{ number_format($item->price, 0, ',', '.') }}/item</small></div>
                            <label class="text-xs text-slate-500">Jumlah<input class="input mt-1 w-24" name="quantity[{{ $item->id }}]" type="number" min="0" max="{{ $remaining }}" value="0"></label>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-3"><label class="min-w-[260px] flex-1 text-sm font-semibold">Alasan retur<input class="input mt-1" name="reason" placeholder="Contoh: barang rusak" required maxlength="1000"></label><button class="btn-primary" type="submit">Proses Retur Supplier</button></div>
        </form>
    @empty
        <div class="card mb-6 text-center text-slate-500">Belum ada pembelian.</div>
    @endforelse

    <div class="card overflow-x-auto p-0">
        <div class="border-b p-5"><h3 class="font-extrabold">Riwayat Retur Supplier</h3></div>
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nomor</th><th class="p-4">Supplier</th><th class="p-4">Nilai</th><th class="p-4">Alasan</th><th class="p-4">Tanggal</th></tr></thead>
            <tbody>
                @forelse($returns as $return)
                    <tr class="border-t"><td class="p-4 font-bold">{{ $return->number }}</td><td class="p-4">{{ $return->supplier_name }}</td><td class="p-4">Rp {{ number_format($return->total, 0, ',', '.') }}</td><td class="p-4">{{ $return->reason }}</td><td class="p-4">{{ \Illuminate\Support\Carbon::parse($return->created_at)->format('d M Y H:i') }}</td></tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada retur supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
