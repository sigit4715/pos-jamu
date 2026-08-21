@extends('layouts.app')

@section('title', 'Penerimaan Transfer')
@section('heading', 'Penerimaan Transfer')

@section('content')
<div class="dashboard-toolbar">
    <div class="dashboard-welcome"><h2>Transfer masuk {{ $store->name }}</h2><p>Masukkan jumlah fisik yang benar-benar diterima sebelum menyimpan.</p></div>
    <span class="status-badge status-waiting">{{ $pendingTransfers->count() }} menunggu</span>
</div>

<div class="space-y-4">
@forelse($pendingTransfers as $transfer)
    <article class="card border-amber-200">
        <div><span class="status-badge status-waiting">Menunggu penerimaan</span><h3 class="mt-2 font-extrabold text-slate-800">{{ $transfer->number }}</h3><p class="mt-1 text-xs text-slate-500">Dari {{ $transfer->sourceStore->name }} oleh {{ $transfer->user->name }} - {{ $transfer->transferred_at->format('d M Y H:i') }}</p></div>
        <form method="POST" action="{{ route('stock-transfers.receive', $transfer) }}" class="mt-4">@csrf
            <div class="grid gap-2 border-t border-slate-100 pt-3 sm:grid-cols-2">@foreach($transfer->items as $item)
                <label class="rounded-lg bg-slate-50 px-3 py-2 text-sm"><b>{{ $item->product_name }}</b><small class="mt-1 block text-slate-400">Dikirim {{ $item->quantity }} {{ $item->unit_name }} ({{ $item->base_quantity }} stok dasar)</small><span class="mt-2 block text-xs font-bold text-slate-600">Jumlah fisik diterima</span><input class="input mt-1" name="received_quantities[{{ $item->id }}]" type="number" min="0" max="{{ $item->quantity }}" value="{{ $item->quantity }}" required></label>
            @endforeach</div>
            <label class="mt-3 block"><span class="label">Catatan selisih (wajib bila jumlah berbeda)</span><textarea class="input" name="difference_notes" rows="2" placeholder="Contoh: 1 botol rusak saat pengiriman"></textarea></label>
            <div class="mt-3 flex justify-end"><button class="btn-primary" type="submit">Simpan Penerimaan</button></div>
        </form>
    </article>
@empty
    <div class="card text-center text-slate-500">Tidak ada transfer yang menunggu penerimaan.</div>
@endforelse
</div>

<section class="card mt-5 overflow-x-auto p-0"><div class="border-b p-5"><h3 class="font-extrabold">Transfer terbaru yang selesai</h3></div><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nomor</th><th class="p-4">Dari</th><th class="p-4">Barang diterima</th><th class="p-4">Status</th></tr></thead><tbody>@forelse($receivedTransfers as $transfer)<tr class="border-t"><td class="p-4 font-bold">{{ $transfer->number }}</td><td class="p-4">{{ $transfer->sourceStore->name }}</td><td class="p-4">@foreach($transfer->items as $item)<div>{{ $item->product_name }}: {{ $item->received_quantity ?? $item->quantity }}/{{ $item->quantity }} {{ $item->unit_name }}</div>@endforeach</td><td class="p-4"><span class="status-badge {{ $transfer->status === 'received' ? 'status-success' : 'status-problem' }}">{{ $transfer->status === 'received' ? 'Diterima' : 'Diterima dengan selisih' }}</span></td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-500">Belum ada transfer diterima.</td></tr>@endforelse</tbody></table></section>
@endsection
