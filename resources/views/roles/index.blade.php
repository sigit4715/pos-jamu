@extends('layouts.app')

@section('title', 'Role & Hak Akses')
@section('heading', 'Role & Hak Akses')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3"><p class="text-sm text-slate-500">Buat jabatan baru atau ubah checklist izin tanpa mengubah kode aplikasi.</p><a class="btn-primary" href="{{ route('roles.create') }}">Tambah Role</a></div>
    <div class="grid gap-5 lg:grid-cols-2">
        @foreach($roles as $role)
            <article class="card">
                <div class="flex items-start justify-between gap-3"><div><div class="flex items-center gap-2"><h2 class="text-lg font-bold text-slate-900">{{ $role->name }}</h2>@if($role->is_system)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">Bawaan</span>@endif</div><p class="mt-1 text-sm text-slate-500">{{ $role->description ?: 'Tanpa deskripsi.' }}</p></div><a class="font-bold text-emerald-600" href="{{ route('roles.edit', $role) }}">Ubah</a></div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div class="rounded-lg bg-slate-50 p-3"><b class="block text-slate-800">{{ $role->permissions->count() }} izin</b><span class="text-slate-500">Akses aktif</span></div><div class="rounded-lg bg-slate-50 p-3"><b class="block text-slate-800">{{ $role->users_count }} pengguna</b><span class="text-slate-500">Menggunakan role</span></div></div>
                <p class="mt-4 text-xs text-slate-500">Cakupan: {{ $role->location_scope === 'all' ? 'semua lokasi' : 'lokasi penugasan' }}{{ $role->location_type ? ' (' . ($role->location_type === 'warehouse' ? 'gudang' : ($role->location_type === 'store' ? 'toko' : 'toko/gudang')) . ')' : '' }}</p>
                @unless($role->is_system)<form method="POST" action="{{ route('roles.destroy', $role) }}" class="mt-3" onsubmit="return confirm('Hapus role ini?')">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-600">Hapus Role</button></form>@endunless
            </article>
        @endforeach
    </div>
@endsection
