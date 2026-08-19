@extends('layouts.app')

@section('title', $role->exists ? 'Ubah Role' : 'Tambah Role')
@section('heading', $role->exists ? 'Ubah Role & Izin' : 'Tambah Role & Izin')

@section('content')
    @php($selected = old('permission_codes', $role->permissions->pluck('code')->all()))
    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" class="space-y-6">
        @csrf @if($role->exists) @method('PUT') @endif
        <div class="card max-w-3xl"><div class="grid gap-5 md:grid-cols-2">
            <div><label class="label">Kode role *</label><input class="input" name="code" value="{{ old('code', $role->code) }}" {{ $role->is_system ? 'readonly' : 'required' }}><p class="mt-1 text-xs text-slate-400">Huruf kecil, angka, garis bawah atau minus.</p></div>
            <div><label class="label">Nama role *</label><input class="input" name="name" value="{{ old('name', $role->name) }}" required></div>
            <div><label class="label">Cakupan lokasi *</label><select class="input" name="location_scope"><option value="assigned" @selected(old('location_scope', $role->location_scope) === 'assigned')>Lokasi penugasan</option><option value="all" @selected(old('location_scope', $role->location_scope) === 'all')>Semua lokasi</option></select></div>
            <div><label class="label">Jenis lokasi tugas</label><select class="input" name="location_type"><option value="any" @selected(old('location_type', $role->location_type) === 'any')>Toko atau gudang</option><option value="store" @selected(old('location_type', $role->location_type) === 'store')>Hanya toko</option><option value="warehouse" @selected(old('location_type', $role->location_type) === 'warehouse')>Hanya gudang</option></select></div>
            <div class="md:col-span-2"><label class="label">Deskripsi</label><textarea class="input" name="description" rows="3">{{ old('description', $role->description) }}</textarea></div>
        </div></div>
        <div class="card"><h2 class="mb-1 text-lg font-bold text-slate-900">Checklist Izin</h2><p class="mb-5 text-sm text-slate-500">Izin menentukan menu dan akses URL. Perubahan berlaku untuk semua akun dengan role ini.</p>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $group => $items)<section class="rounded-xl border border-slate-200 p-4"><h3 class="mb-3 font-bold text-slate-800">{{ $group }}</h3><div class="space-y-3">@foreach($items as $permission)<label class="flex cursor-pointer gap-3 text-sm"><input type="checkbox" class="mt-0.5" name="permission_codes[]" value="{{ $permission->code }}" @checked(in_array($permission->code, $selected, true))><span><b class="block text-slate-700">{{ $permission->name }}</b><small class="text-slate-500">{{ $permission->description }}</small></span></label>@endforeach</div></section>@endforeach
            </div>
        </div>
        <div class="flex gap-3"><button class="btn-primary">Simpan Role</button><a href="{{ route('roles.index') }}" class="btn-muted">Batal</a></div>
    </form>
@endsection
