@extends('layouts.app')
@section('title', 'Audit Aktivitas')
@section('heading', 'Audit Aktivitas')
@section('content')
<div class="mb-6"><h2 class="text-xl font-black">Riwayat aktivitas pengguna</h2><p class="text-sm text-slate-500">Audit perubahan transaksi, stok, kas, promo, dan pengaturan.</p></div>
<form class="card mb-6 flex gap-3" method="GET"><input class="input flex-1" name="action" value="{{ request('action') }}" placeholder="Filter contoh: sale.created"><button class="btn-secondary" type="submit">Filter</button></form>
<div class="card overflow-x-auto p-0"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Waktu</th><th class="p-4">Pengguna</th><th class="p-4">Aksi</th><th class="p-4">Deskripsi</th><th class="p-4">IP</th></tr></thead><tbody>@forelse($logs as $log)<tr class="border-t"><td class="p-4">{{ $log->created_at->format('d M Y H:i:s') }}</td><td class="p-4">{{ $log->user?->name ?? 'Sistem' }}</td><td class="p-4 font-mono text-xs">{{ $log->action }}</td><td class="p-4">{{ $log->description ?? '-' }}</td><td class="p-4 text-slate-500">{{ $log->ip_address ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada aktivitas.</td></tr>@endforelse</tbody></table><div class="border-t p-4">{{ $logs->links() }}</div></div>
@endsection
