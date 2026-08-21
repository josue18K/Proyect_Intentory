<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index() { return view('categories.index', ['items' => Category::withCount('products')->latest()->paginate(10)]); }
    public function create() { return view('categories.form', ['item' => new Category]); }
    public function store(Request $request) { return $this->save($request, new Category); }
    public function edit(Category $category) { return view('categories.form', ['item' => $category]); }
    public function update(Request $request, Category $category) { return $this->save($request, $category); }
    public function destroy(Category $category) { $category->update(['is_active' => false]); return back()->with('success', 'Categoría desactivada.'); }
    private function save(Request $request, Category $category) { $data = $request->validate(['name' => 'required|string|max:255', 'is_active' => 'nullable|boolean']); $data['slug'] = Str::slug($data['name']); $request->validate(['name' => [Rule::unique('categories', 'name')->ignore($category->id)], 'slug' => [Rule::unique('categories', 'slug')->ignore($category->id)]]); $data['is_active'] = $request->boolean('is_active'); $category->fill($data)->save(); return redirect()->route('categories.index')->with('success', 'Categoría guardada.'); }
}
