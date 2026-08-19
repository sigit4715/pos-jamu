@extends('layouts.app')

@section('title', $user->exists ? 'Ubah Akun' : 'Tambah Akun')
@section('heading', $user->exists ? 'Ubah Akun Pengguna' : 'Tambah Akun Pengguna')

@section('content')
    @php($selectedOverrides = $user->permissionOverrides->keyBy('permission_id'))
    @php($oldOverrides = old('permission_overrides', []))
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="space-y-6">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="card max-w-3xl">
            <div class="mb-5"><h2 class="text-lg font-bold text-slate-900">Data Akun</h2><p class="text-sm text-slate-500">Pilih role sebagai template. Hak khusus dapat diatur di bawah.</p></div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="label">Nama lengkap *</label><input class="input" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div><label class="label">Email *</label><input class="input" name="email" type="email" value="{{ old('email', $user->email) }}" required></div>
                <div>
                    <label class="label">Role / Jabatan *</label>
                    <select class="input" name="access_role_id" required>
                        <option value="">Pilih role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('access_role_id', $user->access_role_id) === (string) $role->id)>{{ $role->name }}{{ $role->is_system ? ' (bawaan)' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Role menentukan menu serta izin awal pengguna.</p>
                </div>
                <div>
                    <label class="label">Lokasi penugasan</label>
                    <select class="input" name="store_id">
                        <option value="">Tidak ditugaskan / semua lokasi</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) old('store_id', $user->store_id) === (string) $store->id)>{{ $store->name }} - {{ $store->type === 'warehouse' ? 'Gudang' : 'Toko' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Role dengan cakupan lokasi tertentu wajib diberi toko atau gudang yang sesuai.</p>
                </div>
                <div><label class="label">Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '*' }}</label><input class="input" name="password" type="password" {{ $user->exists ? '' : 'required' }}></div>
                <div><label class="label">Konfirmasi password {{ $user->exists ? '' : '*' }}</label><input class="input" name="password_confirmation" type="password" {{ $user->exists ? '' : 'required' }}></div>
            </div>
        </div>

        <div class="card max-w-5xl">
            <div class="mb-5"><h2 class="text-lg font-bold text-slate-900">Izin Khusus Pengguna</h2><p class="text-sm text-slate-500">Biarkan “Ikuti role” untuk memakai template role. Gunakan Izinkan atau Tolak hanya bila akun ini membutuhkan pengecualian.</p></div>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $group => $items)
                    <section class="rounded-xl border border-slate-200 p-4">
                        <h3 class="mb-3 font-bold text-slate-800">{{ $group }}</h3>
                        <div class="space-y-3">
                            @foreach($items as $permission)
                                @php($default = $selectedOverrides->has($permission->id) ? ($selectedOverrides[$permission->id]->is_allowed ? 'allow' : 'deny') : 'inherit')
                                @php($state = $oldOverrides[$permission->id] ?? $default)
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">{{ $permission->name }}</label>
                                    <select class="input mt-1 text-sm" name="permission_overrides[{{ $permission->id }}]">
                                        <option value="inherit" @selected($state === 'inherit')>Ikuti role</option>
                                        <option value="allow" @selected($state === 'allow')>Izinkan</option>
                                        <option value="deny" @selected($state === 'deny')>Tolak</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3"><button class="btn-primary">Simpan Akun</button><a href="{{ route('users.index') }}" class="btn-muted">Batal</a></div>
    </form>
@endsection
