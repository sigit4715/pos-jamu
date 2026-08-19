@extends('layouts.app')

@section('title', 'Pengaturan Menu')
@section('heading', 'Pengaturan Sidebar')

@section('content')
    <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">Menu di bawah adalah katalog halaman aplikasi yang aman. Anda dapat menonaktifkan, mengganti label, kelompok, urutan, ikon, dan izin pemilik menu. URL aplikasi tidak dapat dibuat bebas dari panel ini.</div>
    <div class="space-y-6">
        @foreach($menus as $section => $items)
            <section class="card overflow-x-auto"><h2 class="mb-4 text-lg font-bold text-slate-900">{{ $section }}</h2><table class="min-w-[900px] w-full text-left text-sm"><thead class="text-slate-500"><tr><th class="pb-3">Menu</th><th class="pb-3">Kelompok</th><th class="pb-3">Ikon</th><th class="pb-3">Izin yang dibutuhkan</th><th class="pb-3">Urutan</th><th class="pb-3">Tampil</th><th class="pb-3"></th></tr></thead><tbody>
                @foreach($items as $menu)<tr class="border-t border-slate-100"><td class="py-3 pr-3"><form id="menu-form-{{ $menu->id }}" method="POST" action="{{ route('menus.update', $menu) }}">@csrf @method('PUT')<input class="input" name="name" value="{{ $menu->name }}"></form><small class="mt-1 block text-slate-400">{{ $menu->route_name }}</small></td>
                    <td class="py-3 pr-3"><input form="menu-form-{{ $menu->id }}" class="input" name="section" value="{{ $menu->section }}"></td>
                    <td class="py-3 pr-3"><select form="menu-form-{{ $menu->id }}" class="input" name="icon">@foreach($icons as $icon)<option value="{{ $icon }}" @selected($menu->icon === $icon)>{{ $icon }}</option>@endforeach</select></td>
                    <td class="py-3 pr-3"><select form="menu-form-{{ $menu->id }}" class="input" name="permission_id">@foreach($permissions as $permission)<option value="{{ $permission->id }}" @selected($menu->permission_id === $permission->id)>{{ $permission->group_name }} - {{ $permission->name }}</option>@endforeach</select></td>
                    <td class="py-3 pr-3"><input form="menu-form-{{ $menu->id }}" class="input w-20" type="number" min="0" name="sort_order" value="{{ $menu->sort_order }}"></td>
                    <td class="py-3 pr-3"><input form="menu-form-{{ $menu->id }}" type="hidden" name="is_active" value="0"><input form="menu-form-{{ $menu->id }}" type="checkbox" name="is_active" value="1" @checked($menu->is_active)></td>
                    <td class="py-3"><button form="menu-form-{{ $menu->id }}" class="font-bold text-emerald-600">Simpan</button></td>
                </tr>@endforeach
            </tbody></table></section>
        @endforeach
    </div>
@endsection
