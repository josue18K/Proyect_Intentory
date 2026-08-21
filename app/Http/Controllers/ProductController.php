<?php

namespace App\Http\Controllers;

use App\Models\{Category, Product};
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() { return view('products.index', ['items' => Product::with('category')->latest()->paginate(10)]); }
    public function create() { return view('products.form', ['item' => new Product, 'categories' => Category::where('is_active', true)->orderBy('name')->get()]); }
    public function store(Request $request) { return $this->save($request, new Product); }
    public function edit(Product $product) { return view('products.form', ['item' => $product, 'categories' => Category::orderBy('name')->get()]); }
    public function update(Request $request, Product $product) { return $this->save($request, $product); }
    public function destroy(Product $product) { $product->update(['is_active' => false]); return back()->with('success', 'Producto desactivado.'); }
    public function history(Request $request, Product $product) { abort_unless($request->user()->can('inventory.view'), 403); $items = $product->movements()->with(['branch', 'user'])->when($request->user()->role !== 'administrador', fn($q) => $q->whereIn('branch_id', $request->user()->branches()->pluck('branches.id')))->latest()->paginate(15)->withQueryString(); return view('products.history', compact('product', 'items')); }
    private function save(Request $request, Product $product) { $data = $request->validate(['category_id' => 'required|exists:categories,id', 'internal_code' => 'required|max:255|unique:products,internal_code,'.($product->id ?? 'NULL'), 'barcode' => 'nullable|max:255|unique:products,barcode,'.($product->id ?? 'NULL'), 'name' => 'required|max:255', 'description' => 'nullable|string', 'brand' => 'nullable|max:255', 'purchase_price' => 'required|numeric|min:0', 'sale_price' => 'required|numeric|min:0', 'minimum_stock' => 'required|integer|min:0', 'is_active' => 'nullable|boolean']); $data['is_active'] = $request->boolean('is_active'); $product->fill($data)->save(); return redirect()->route('products.index')->with('success', 'Producto guardado.'); }
}
