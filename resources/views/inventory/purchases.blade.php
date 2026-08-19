@extends('layouts.app')

@section('title', 'Pembelian dan Barang Masuk')
@section('heading', 'Pembelian & Barang Masuk')

@section('content')
<div class="mb-6"><h2 class="text-xl font-black">Input pembelian supplier</h2><p class="text-sm text-slate-500">Pilih satuan dasar atau kemasan. Contoh beli 2 karton isi 24 pcs akan menambah 48 pcs ke stok.</p></div>
<form class="card" method="POST" action="{{ route('purchases.store') }}">
    @csrf
    <div class="grid gap-4 md:grid-cols-4"><div><label class="label">Supplier</label><select class="input" name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div><div><label class="label">Status pembayaran</label><select class="input" name="payment_status"><option value="paid">Lunas</option><option value="partial">Sebagian</option><option value="credit">Kredit / Hutang</option></select></div><div><label class="label">Jatuh tempo</label><input class="input" name="due_date" type="date"></div><div><label class="label">Sudah dibayar</label><input class="input" name="paid_amount" type="number" min="0" step="0.01" value="0"></div></div>
    <input class="input mt-4" name="notes" placeholder="Catatan pembelian">
    <div id="lines" class="mt-4 space-y-3"></div>
    <button type="button" class="btn-muted mt-3" id="add">Tambah Barang</button>
    <div class="mt-5 flex justify-end"><button class="btn-primary" type="submit">Simpan Pembelian</button></div>
</form>
<div class="card mt-6 overflow-x-auto p-0"><div class="border-b p-5"><h3 class="font-extrabold">Riwayat Pembelian</h3><p class="text-xs text-slate-500">Semua barang masuk dan status pembayaran tercatat di sini.</p></div><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Nomor</th><th class="p-4">Supplier</th><th class="p-4">Status</th><th class="p-4">Total</th><th class="p-4">Sisa</th><th class="p-4">Tanggal</th></tr></thead><tbody>@forelse($purchases as $purchase)<tr class="border-t"><td class="p-4 font-bold">{{ $purchase->number }}</td><td class="p-4">{{ $purchase->supplier->name }}</td><td class="p-4">{{ ucfirst($purchase->payment_status ?? 'paid') }}</td><td class="p-4 font-bold text-emerald-600">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td><td class="p-4 {{ $purchase->outstanding > 0 ? 'text-red-600 font-bold' : '' }}">Rp {{ number_format($purchase->outstanding, 0, ',', '.') }}</td><td class="p-4 text-slate-500">{{ $purchase->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada pembelian.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $purchases->links() }}</div>
<script>
const catalog = @json($productCatalog);
const lines = document.getElementById('lines');
let lineIndex = 0;
const productOptions = '<option value="">Produk</option>' + Object.entries(catalog).map(([id, product]) => `<option value="${id}">${product.name} (stok ${product.stock} ${product.unit})</option>`).join('');
function unitOptions(productId) { const product = catalog[productId]; if (!product) return '<option value="">Pilih produk dulu</option>'; return `<option value="">${product.unit} (1 ${product.unit})</option>` + product.packagings.map(packaging => `<option value="${packaging.id}">${packaging.name} (${packaging.conversion} ${product.unit})</option>`).join(''); }
function addLine(productId = null) { const index = lineIndex++; const line = document.createElement('div'); line.className = 'rounded-xl bg-slate-50 p-3'; line.innerHTML = `<div class="grid gap-2 md:grid-cols-4 xl:grid-cols-7"><select class="input product" name="items[${index}][product_id]" required>${productOptions}</select><select class="input package" name="items[${index}][product_packaging_id]"></select><input class="input" name="items[${index}][quantity]" type="number" min="1" placeholder="Jumlah" required><input class="input" name="items[${index}][price]" type="number" min="0" placeholder="Harga beli/satuan" required><input class="input" name="items[${index}][batch_number]" placeholder="No. batch"><input class="input" name="items[${index}][expires_at]" type="date" title="Kedaluwarsa"><button type="button" class="btn-muted remove">Hapus</button></div><p class="mt-2 unit-note text-xs text-slate-500"></p>`; const product = line.querySelector('.product'), pack = line.querySelector('.package'); function sync(){pack.innerHTML=unitOptions(product.value);const info=catalog[product.value];line.querySelector('.unit-note').textContent=info?'Harga beli diisi per satuan yang dipilih. Stok akan dihitung dalam '+info.unit+'.':'';} product.onchange=sync; line.querySelector('.remove').onclick=()=>line.remove(); lines.appendChild(line); if(productId){product.value=productId;sync();}else sync(); }
addLine(@json($suggestedProductId)); document.getElementById('add').onclick=()=>addLine();
</script>
@endsection
