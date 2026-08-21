<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index() { return view('branches.index', ['items' => Branch::withCount('inventories')->latest()->paginate(10)]); }
    public function create() { return view('branches.form', ['item' => new Branch]); }
    public function store(Request $request) { return $this->save($request, new Branch); }
    public function edit(Branch $branch) { return view('branches.form', ['item' => $branch]); }
    public function update(Request $request, Branch $branch) { return $this->save($request, $branch); }
    public function destroy(Branch $branch) { $branch->update(['is_active' => false]); return back()->with('success', 'Sede desactivada.'); }
    private function save(Request $request, Branch $branch) { $data = $request->validate(['name' => 'required|string|max:255', 'is_active' => 'nullable|boolean']); $data['slug'] = Str::slug($data['name']); $request->validate(['name' => [Rule::unique('branches', 'name')->ignore($branch->id)], 'slug' => [Rule::unique('branches', 'slug')->ignore($branch->id)]]); $data['is_active'] = $request->boolean('is_active'); $branch->fill($data)->save(); return redirect()->route('branches.index')->with('success', 'Sede guardada.'); }
}
