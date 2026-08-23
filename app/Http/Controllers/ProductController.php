<?php

namespace App\Http\Controllers;

use App\Models\{Branch, Category, Inventory, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request) { $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id'); $items = Product::with('category')->where('is_active', true)->when($allowed, fn ($q) => $q->whereHas('inventories', fn ($i) => $i->whereIn('branch_id', $allowed)))->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('internal_code', 'like', "%{$v}%")->orWhere('barcode', 'like', "%{$v}%")->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$v}%"))))->latest()->paginate(10)->withQueryString(); return view('products.index', ['items' => $items, 'searchable' => true]); }
    public function create() { return view('products.form', ['item' => new Product, 'categories' => Category::where('is_active', true)->orderBy('name')->get(), 'branches' => Branch::where('is_active', true)->orderBy('name')->get()]); }
    public function store(Request $request) { return $this->save($request, new Product); }
    public function edit(Product $product) { return view('products.form', ['item' => $product, 'categories' => Category::orderBy('name')->get(), 'branches' => Branch::where('is_active', true)->orderBy('name')->get()]); }
    public function update(Request $request, Product $product) { return $this->save($request, $product); }
    public function destroy(Product $product) { $product->update(['is_active' => false]); return back()->with('success', 'Producto desactivado.'); }
    public function history(Request $request, Product $product) { abort_unless($request->user()->can('inventory.view'), 403); $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id'); abort_unless(! $allowed || $product->inventories()->whereIn('branch_id', $allowed)->exists(), 404); $items = $product->movements()->with(['branch', 'user'])->when($allowed, fn($q) => $q->whereIn('branch_id', $allowed))->latest()->paginate(15)->withQueryString(); return view('products.history', compact('product', 'items')); }
    private function save(Request $request, Product $product) { $data = $request->validate(['category_id' => 'required|exists:categories,id', 'internal_code' => 'nullable|max:255|unique:products,internal_code,'.($product->id ?? 'NULL'), 'barcode' => 'nullable|max:255|unique:products,barcode,'.($product->id ?? 'NULL'), 'name' => 'required|max:255', 'description' => 'nullable|string', 'brand' => 'nullable|max:255', 'sale_price' => 'required|numeric|min:0', 'minimum_stock' => 'required|integer|min:0', 'branch_ids' => ['required', 'array', 'min:1'], 'branch_ids.*' => [Rule::exists('branches', 'id')->where('is_active', true)], 'is_active' => 'nullable|boolean']); $data['is_active'] = $request->boolean('is_active'); $data['purchase_price'] = 0; DB::transaction(function () use (&$product, $data) { if (! $product->exists) { do { $data['internal_code'] = 'LIU-' . str_pad((string) ((Product::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT); } while (Product::where('internal_code', $data['internal_code'])->exists()); } $product->fill($data)->save(); $selected = collect($data['branch_ids'])->map(fn ($id) => (int) $id); foreach ($selected as $branchId) Inventory::firstOrCreate(['product_id' => $product->id, 'branch_id' => $branchId], ['quantity' => 0]); Inventory::where('product_id', $product->id)->whereNotIn('branch_id', $selected)->where('quantity', 0)->delete(); }); return redirect()->route('products.index')->with('success', 'Producto guardado con sus sedes.'); }
}
