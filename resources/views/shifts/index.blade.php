@extends('layouts.app')
@section('title', 'Shift Kasir')
@section('heading', 'Shift Kasir')
@section('content')
<div class="mb-6"><h2 class="text-xl font-black">Buka dan tutup kasir</h2><p class="text-sm text-slate-500">Catat uang awal, pantau uang tunai yang seharusnya ada, dan rekonsiliasi saat tutup shift.</p></div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-1">
        @if($current)
            <div class="mb-4 flex items-center justify-between"><h3 class="font-extrabold">Shift sedang berjalan</h3><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">OPEN</span></div>
            <div class="space-y-2 text-sm"><p class="flex justify-between"><span class="text-slate-500">Mulai</span><b>{{ $current->opened_at->format('d M Y H:i') }}</b></p><p class="flex justify-between"><span class="text-slate-500">Uang awal</span><b>Rp {{ number_format($current->opening_cash, 0, ',', '.') }}</b></p><p class="flex justify-between"><span class="text-slate-500">Seharusnya</span><b class="text-emerald-600">Rp {{ number_format($expected, 0, ',', '.') }}</b></p></div>
            <form method="POST" action="{{ route('shifts.close') }}" class="mt-5 border-t pt-4">@csrf<label class="label">Uang fisik saat tutup</label><input class="input" name="closing_cash" type="number" min="0" step="0.01" required placeholder="0"><label class="label mt-3">Catatan</label><input class="input" name="notes" maxlength="500"><button class="btn-primary mt-4 w-full" type="submit">Tutup Shift</button></form>
        @else
            <h3 class="mb-4 font-extrabold">Belum ada shift aktif</h3><p class="mb-4 text-sm text-slate-500">Buka shift sebelum mulai transaksi agar penjualan tunai tercatat dalam rekonsiliasi.</p><form method="POST" action="{{ route('shifts.open') }}">@csrf<label class="label">Uang awal kasir</label><input class="input" name="opening_cash" type="number" min="0" step="0.01" value="0" required><label class="label mt-3">Catatan</label><input class="input" name="notes" maxlength="500"><button class="btn-primary mt-4 w-full" type="submit">Buka Shift</button></form>
        @endif
    </div>
    <div class="card overflow-x-auto p-0 lg:col-span-2"><div class="border-b p-5"><h3 class="font-extrabold">Riwayat shift</h3></div><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Petugas</th><th class="p-4">Mulai</th><th class="p-4">Selesai</th><th class="p-4">Uang awal</th><th class="p-4">Selisih</th><th class="p-4">Status</th></tr></thead><tbody>@forelse($shifts as $shift)<tr class="border-t"><td class="p-4 font-semibold">{{ $shift->user->name }}</td><td class="p-4">{{ $shift->opened_at->format('d M Y H:i') }}</td><td class="p-4">{{ $shift->closed_at?->format('d M Y H:i') ?? '-' }}</td><td class="p-4">Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</td><td class="p-4">{{ $shift->difference === null ? '-' : 'Rp '.number_format($shift->difference, 0, ',', '.') }}</td><td class="p-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $shift->status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($shift->status) }}</span></td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada riwayat shift.</td></tr>@endforelse</tbody></table><div class="border-t p-4">{{ $shifts->links() }}</div></div>
</div>
@endsection
