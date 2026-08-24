@extends('layouts.app')

@section('title', $product->exists ? 'Ubah Barang' : 'Tambah Barang')
@section('heading', $product->exists ? 'Ubah Master Barang' : 'Tambah Master Barang')

@section('content')
<form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="card max-w-5xl">
    @csrf
    @if($product->exists) @method('PUT') @endif
    <div class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Stok disimpan dalam satuan dasar. Tambahkan kemasan seperti karton untuk menjual beberapa satuan dasar dengan harga sendiri.</div>

    <div class="grid gap-5 md:grid-cols-2">
        <div><label class="label">Nama barang *</label><input class="input" name="name" value="{{ old('name', $product->name) }}" required></div>
        <div><label class="label">Kode barang *</label><input class="input" name="code" value="{{ old('code', $product->code) }}" required></div>
        <div><label class="label">Barcode satuan dasar</label><input class="input" name="barcode" value="{{ old('barcode', $product->barcode) }}" placeholder="Scan atau ketik barcode"></div>
        <div><label class="label">Kategori</label><select class="input" name="category_id"><option value="">Pilih kategori</option>@foreach($categories as $row)<option value="{{ $row->id }}" @selected(old('category_id', $product->category_id) == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
        <div><label class="label">Merek</label><select class="input" name="brand_id"><option value="">Pilih merek</option>@foreach($brands as $row)<option value="{{ $row->id }}" @selected(old('brand_id', $product->brand_id) == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
        <div><label class="label">Supplier utama</label><select class="input" name="supplier_id"><option value="">Pilih supplier</option>@foreach($suppliers as $row)<option value="{{ $row->id }}" @selected(old('supplier_id', $product->supplier_id) == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
        <div><div class="flex items-center justify-between gap-2"><label class="label">Satuan dasar *</label><span class="mb-2 flex items-center gap-3"><button class="text-xs font-bold text-emerald-700" id="add-unit" type="button">+ Satuan baru</button>@if(auth()->user()->hasPermission('master.manage'))<a class="text-xs font-bold text-slate-600 underline underline-offset-2 hover:text-emerald-700" href="{{ route('master.index') }}#satuan" target="_blank" rel="noopener">Kelola Satuan</a>@endif</span></div><select class="input" id="unit_id" name="unit_id"><option value="">Pilih satuan</option>@foreach($units as $row)<option value="{{ $row->id }}" data-name="{{ $row->name }}" @selected(old('unit_id', $product->unit_id) == $row->id)>{{ $row->name }}{{ $row->symbol ? ' ('.$row->symbol.')' : '' }}</option>@endforeach</select><input type="hidden" id="unit" name="unit" value="{{ old('unit', $product->unit ?: 'pcs') }}"></div>
        <div><label class="label">Harga jual satuan dasar *</label><input class="input" name="price" type="number" min="0" value="{{ old('price', $product->price) }}" required></div>
        <div><label class="label">Harga beli per satuan dasar *</label><input class="input" name="buy_price" type="number" min="0" value="{{ old('buy_price', $product->buy_price ?? 0) }}" required></div>
        <div><label class="label">Stok awal / stok dasar *</label><input class="input" name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required></div>
        <div><label class="label">Minimum stok dasar *</label><input class="input" name="minimum_stock" type="number" min="0" value="{{ old('minimum_stock', $product->minimum_stock ?? 5) }}" required></div>
        <div><label class="label">Status</label><label class="mt-3 flex items-center gap-2 text-sm font-semibold"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))> Barang aktif dan tampil di kasir</label></div>
        <div class="md:col-span-2"><label class="label">Keterangan</label><textarea class="input" name="description" rows="3">{{ old('description', $product->description) }}</textarea></div>
    </div>

    <div class="mt-7 border-t border-slate-100 pt-5">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-extrabold">Kemasan dan harga jual</h3><p class="mt-1 text-xs text-slate-500">Contoh: Karton berisi 24 pcs dengan harga jual berbeda dari pcs.</p></div><button type="button" class="btn-muted" id="add-packaging">Tambah Kemasan</button></div>
        <div id="packagings" class="mt-4 space-y-3"></div>
    </div>

    <div class="mt-6 flex gap-3"><button class="btn-primary">Simpan Master Barang</button><a class="btn-muted" href="{{ route('products.index') }}">Batal</a></div>
</form>

<dialog class="w-full max-w-md rounded-2xl border border-slate-200 p-0 shadow-xl" id="unit-dialog">
    <form class="p-6" id="unit-form" method="dialog">
        <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black">Tambah satuan baru</h2><p class="mt-1 text-sm text-slate-500">Satuan baru langsung aktif dan dipilih untuk barang ini.</p></div><button class="text-xl text-slate-400" type="button" id="close-unit-dialog" aria-label="Tutup">×</button></div>
        <div class="mt-5 grid gap-4"><div><label class="label" for="new-unit-name">Nama satuan *</label><input class="input" id="new-unit-name" maxlength="40" placeholder="Contoh: Botol" required></div><div><label class="label" for="new-unit-symbol">Simbol</label><input class="input" id="new-unit-symbol" maxlength="15" placeholder="Contoh: btl"></div><p class="hidden text-sm font-semibold text-red-600" id="unit-error"></p></div>
        <div class="mt-6 flex justify-end gap-3"><button class="btn-muted" type="button" id="cancel-unit">Batal</button><button class="btn-primary" id="save-unit" type="submit">Simpan satuan</button></div>
    </form>
</dialog>

<script>
const rows = @json(old('packagings', $packagingRows));
const packagings = document.getElementById('packagings');
let packagingIndex = 0;
const esc = value => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
function addPackaging(row = {}) {
    const index = packagingIndex++;
    const card = document.createElement('div');
    card.className = 'rounded-xl border border-slate-200 bg-slate-50 p-4';
    card.dataset.existing = row.id ? '1' : '0';
    card.innerHTML = `<input type="hidden" name="packagings[${index}][id]" value="${esc(row.id)}"><div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_220px_auto]"><div><label class="label">Nama kemasan</label><input class="input" name="packagings[${index}][name]" value="${esc(row.name)}" placeholder="Contoh: Karton" required></div><div><label class="label">Isi satuan dasar</label><input class="input" name="packagings[${index}][conversion_quantity]" type="number" min="2" value="${esc(row.conversion_quantity)}" placeholder="Contoh: 24" required></div><div><label class="label">Harga jual kemasan</label><input class="input" name="packagings[${index}][price]" type="number" min="0" value="${esc(row.price)}" placeholder="Contoh: 120000" required></div><div class="flex items-end gap-2"><input type="hidden" name="packagings[${index}][is_active]" value="0"><label class="mb-2 flex items-center gap-2 whitespace-nowrap text-xs font-semibold"><input name="packagings[${index}][is_active]" type="checkbox" value="1" ${row.is_active === false || row.is_active === 0 ? '' : 'checked'}> Aktif</label><button type="button" class="btn-muted remove-packaging">Hapus</button></div></div>`;
    card.querySelector('.remove-packaging').onclick = () => {
        if (card.dataset.existing === '1') {
            card.querySelector('input[type="checkbox"]').checked = false;
            card.classList.add('hidden');
        } else {
            card.remove();
        }
    };
    packagings.appendChild(card);
}
rows.forEach(addPackaging);
document.getElementById('add-packaging').onclick = () => addPackaging({is_active: true});
document.getElementById('unit_id').addEventListener('change', event => {
    const option = event.target.selectedOptions[0];
    if (option.dataset.name) document.getElementById('unit').value = option.dataset.name;
});

const unitDialog = document.getElementById('unit-dialog');
const unitForm = document.getElementById('unit-form');
const unitError = document.getElementById('unit-error');
const newUnitName = document.getElementById('new-unit-name');
const newUnitSymbol = document.getElementById('new-unit-symbol');
document.getElementById('add-unit').onclick = () => { unitError.classList.add('hidden'); unitForm.reset(); unitDialog.showModal(); newUnitName.focus(); };
document.getElementById('close-unit-dialog').onclick = document.getElementById('cancel-unit').onclick = () => unitDialog.close();
unitForm.onsubmit = async event => {
    event.preventDefault();
    unitError.classList.add('hidden');
    const saveButton = document.getElementById('save-unit');
    saveButton.disabled = true;
    try {
        const response = await fetch(@json(route('products.units.store')), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ name: newUnitName.value, symbol: newUnitSymbol.value }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(Object.values(payload.errors ?? {})[0]?.[0] ?? 'Satuan tidak dapat disimpan.');
        const option = new Option(payload.name + (payload.symbol ? ' (' + payload.symbol + ')' : ''), payload.id, true, true);
        option.dataset.name = payload.name;
        document.getElementById('unit_id').add(option);
        document.getElementById('unit').value = payload.name;
        unitDialog.close();
    } catch (error) {
        unitError.textContent = error.message;
        unitError.classList.remove('hidden');
    } finally {
        saveButton.disabled = false;
    }
};
</script>
@endsection
