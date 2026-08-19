@extends('layouts.app')
@section('heading','Stock Opname')
@section('content')
<form method="POST" class="card">@csrf
<p class="mb-4 text-sm text-slate-500">Masukkan jumlah stok fisik. Baris dengan selisih akan tercatat sebagai penyesuaian.</p>
<div class="space-y-2">@foreach($products as $p)<div class="grid items-center gap-2 rounded-xl bg-slate-50 p-3 md:grid-cols-4"><span><b>{{ $p->name }}</b><small class="block">Stok sistem: {{ $p->stock }}</small></span><input class="input" name="physical[{{ $p->id }}]" type="number" min="0" value="{{ $p->stock }}" @disabled(!$canEdit)><input class="input" name="reason[{{ $p->id }}]" placeholder="Alasan selisih" @disabled(!$canEdit)><span class="text-sm text-slate-500">{{ $p->code }}</span></div>@endforeach</div>
@if($canEdit)<button class="btn-primary mt-5">Simpan Stock Opname</button>@else<p class="mt-5 rounded-xl bg-blue-50 p-3 text-sm text-blue-700">Mode lihat saja untuk kasir. Penyesuaian stock opname dilakukan admin.</p>@endif
</form>@endsection
