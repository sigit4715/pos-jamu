@extends('layouts.app')

@section('title', 'Pengguna')
@section('heading', 'Pengguna & Hak Akses')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Setiap akun memakai role yang dapat dikustomisasi, dengan pengecualian izin bila diperlukan.</p>
        <a class="btn-primary" href="{{ route('users.create') }}">Tambah Akun</a>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nama</th><th class="p-4">Email</th><th class="p-4">Role</th><th class="p-4">Lokasi</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-t border-slate-100">
                        <td class="p-4 font-bold">{{ $user->name }}</td>
                        <td class="p-4">{{ $user->email }}</td>
                        <td class="p-4"><span class="rounded-lg bg-violet-50 px-2 py-1 text-xs font-bold text-violet-700">{{ $user->roleLabel() }}</span></td>
                        <td class="p-4 text-slate-600">{{ $user->canAccessAllLocations() ? 'Semua lokasi' : ($user->store?->name ?? 'Belum ditugaskan') }}</td>
                        <td class="p-4 text-xs text-slate-500">{{ $user->is_system_owner ? 'Pemilik Sistem' : 'Akun aktif' }}</td>
                        <td class="p-4 text-right"><a class="font-bold text-emerald-600" href="{{ route('users.edit', $user) }}">Ubah</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
