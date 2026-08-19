<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AuditService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')->when($request->search, fn ($q, $search) => $q->where(fn ($x) => $x->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%")))->latest()->paginate(12)->withQueryString();
        return view('products.index', compact('products'));
    }
    private function formData(Product $product): array { return ['product'=>$product,'categories'=>Category::orderBy('name')->get(),'units'=>Unit::where('is_active',1)->orderBy('name')->get(),'brands'=>Brand::where('is_active',1)->orderBy('name')->get(),'suppliers'=>Supplier::where('is_active',1)->orderBy('name')->get()]; }
    public function create() { return view('products.form', $this->formData(new Product)); }
    public function store(Request $request) { $product = $this->save($request, new Product); AuditService::log('product.created', $product, 'Master barang ditambahkan'); return redirect()->route('products.index')->with('success', "Produk {$product->name} ditambahkan."); }
    public function edit(Product $product) { return view('products.form', $this->formData($product)); }
    public function update(Request $request, Product $product) { $this->save($request, $product); AuditService::log('product.updated', $product, 'Master barang diperbarui'); return redirect()->route('products.index')->with('success', 'Produk diperbarui.'); }
    private function save(Request $request, Product $product): Product
    {
        $data = $request->validate(['category_id' => ['nullable', 'exists:categories,id'], 'unit_id' => ['nullable','exists:units,id'], 'brand_id' => ['nullable','exists:brands,id'], 'supplier_id' => ['nullable','exists:suppliers,id'], 'code' => ['required', 'max:30', Rule::unique('products', 'code')->ignore($product)], 'barcode' => ['nullable', 'max:50', Rule::unique('products', 'barcode')->ignore($product)], 'name' => ['required', 'max:120'], 'description' => ['nullable'], 'price' => ['required', 'numeric', 'min:0'], 'buy_price' => ['required', 'numeric', 'min:0'], 'stock' => ['required', 'integer', 'min:0'], 'minimum_stock' => ['required', 'integer', 'min:0'], 'unit' => ['required', 'max:20'], 'is_active' => ['nullable', 'boolean']]);
        $before = $product->exists ? $product->stock : 0; $data['is_active'] = $request->boolean('is_active');
        return DB::transaction(function () use ($product, $data, $before) { $product->fill($data)->save(); if ($before !== (int) $product->stock) StockLog::create(['product_id' => $product->id, 'user_id' => auth()->id(), 'type' => $product->wasRecentlyCreated ? 'initial' : 'adjustment', 'quantity_change' => $product->stock - $before, 'stock_before' => $before, 'stock_after' => $product->stock, 'reference' => $product->code, 'notes' => 'Penyesuaian dari master produk']); return $product; });
    }
}
