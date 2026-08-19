<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('is_active', true)->when($request->filled('q'), fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', '%' . $request->q . '%')->orWhere('code', 'like', '%' . $request->q . '%')->orWhere('barcode', 'like', '%' . $request->q . '%')))->orderBy('name')->paginate(30)->withQueryString();
        return view('barcodes.index', compact('products'));
    }

    public function print(Product $product)
    {
        abort_unless($product->is_active, 404);
        return view('barcodes.print', compact('product'));
    }
}
