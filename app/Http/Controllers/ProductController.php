<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\AuditService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('store_id', $this->storeId())
            ->with(['category', 'packagings'])
            ->when($request->search, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', $this->formData(new Product));
    }

    public function store(Request $request)
    {
        $product = $this->save($request, new Product);
        AuditService::log('product.created', $product, 'Master barang ditambahkan');

        return redirect()->route('products.index')->with('success', "Produk {$product->name} ditambahkan.");
    }

    public function edit(Product $product)
    {
        $this->ensureStore($product);

        return view('products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product)
    {
        $this->ensureStore($product);
        $this->save($request, $product);
        AuditService::log('product.updated', $product, 'Master barang diperbarui');

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    private function formData(Product $product): array
    {
        $product->load('packagings');

        return [
            'product' => $product,
            'packagingRows' => $product->packagings->map(fn (ProductPackaging $packaging) => [
                'id' => $packaging->id,
                'name' => $packaging->name,
                'conversion_quantity' => $packaging->conversion_quantity,
                'price' => $packaging->price,
                'is_active' => $packaging->is_active,
            ])->values()->all(),
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::where('is_active', 1)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', 1)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', 1)->orderBy('name')->get(),
        ];
    }

    private function save(Request $request, Product $product): Product
    {
        $storeId = $this->storeId();
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'code' => ['required', 'max:30', Rule::unique('products', 'code')->where('store_id', $storeId)->ignore($product)],
            'barcode' => ['nullable', 'max:50', Rule::unique('products', 'barcode')->where('store_id', $storeId)->ignore($product)],
            'name' => ['required', 'max:120'],
            'description' => ['nullable'],
            'price' => ['required', 'numeric', 'min:0'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'packagings' => ['nullable', 'array'],
            'packagings.*.id' => ['nullable', 'integer', 'exists:product_packagings,id'],
            'packagings.*.name' => ['required', 'string', 'max:40'],
            'packagings.*.conversion_quantity' => ['required', 'integer', 'min:2'],
            'packagings.*.price' => ['required', 'numeric', 'min:0'],
            'packagings.*.is_active' => ['nullable', 'boolean'],
        ]);

        $before = $product->exists ? (int) $product->stock : 0;
        $packagings = $data['packagings'] ?? [];
        unset($data['packagings']);
        $data['is_active'] = $request->boolean('is_active');
        $data['store_id'] = $storeId;

        return DB::transaction(function () use ($product, $data, $before, $storeId, $packagings) {
            $product->fill($data)->save();
            $this->syncPackagings($product, $packagings);

            if ($before !== (int) $product->stock) {
                StockLog::create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => $product->wasRecentlyCreated ? 'initial' : 'adjustment',
                    'quantity_change' => (int) $product->stock - $before,
                    'transaction_quantity' => abs((int) $product->stock - $before),
                    'unit_name' => $product->unit,
                    'conversion_quantity' => 1,
                    'stock_before' => $before,
                    'stock_after' => $product->stock,
                    'reference' => $product->code,
                    'notes' => 'Penyesuaian dari master produk',
                ]);
            }

            return $product;
        });
    }

    private function syncPackagings(Product $product, array $rows): void
    {
        foreach ($rows as $row) {
            $packaging = null;
            if (! empty($row['id'])) {
                $packaging = ProductPackaging::where('product_id', $product->id)->find($row['id']);
                if (! $packaging) {
                    throw ValidationException::withMessages(['packagings' => 'Kemasan tidak sesuai dengan barang ini.']);
                }
            }

            $payload = [
                'name' => trim($row['name']),
                'conversion_quantity' => (int) $row['conversion_quantity'],
                'price' => $row['price'],
                'is_active' => (bool) ($row['is_active'] ?? false),
            ];

            if ($packaging) {
                $packaging->update($payload);
            } else {
                $product->packagings()->create($payload);
            }
        }
    }

    private function storeId(): int
    {
        return app(StoreContext::class)->id();
    }

    private function ensureStore(Product $product): void
    {
        abort_unless($product->store_id === $this->storeId(), 404);
    }
}
