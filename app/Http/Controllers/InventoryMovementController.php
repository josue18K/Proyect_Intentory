<?php

namespace App\Http\Controllers;

use App\Models\{Branch, Inventory, InventoryMovement, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class InventoryMovementController extends Controller
{
    public function index(Request $request) { abort_unless($request->user()->can('inventory.view'), 403); $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id'); $items = InventoryMovement::with(['product', 'branch', 'user'])->when($allowed, fn($q) => $q->whereIn('branch_id', $allowed))->when($request->search, fn($q,$v) => $q->whereHas('product', fn($p) => $p->where('name','like','%'.$v.'%')->orWhere('internal_code','like','%'.$v.'%')))->when($request->branch_id, fn($q,$v) => $q->where('branch_id',$v))->when($request->type, fn($q,$v) => $q->where('type',$v))->latest()->paginate(15)->withQueryString(); return view('movements.index', ['items'=>$items, 'branches'=>Branch::where('is_active',true)->when($allowed, fn($q) => $q->whereIn('id',$allowed))->orderBy('name')->get()]); }
    public function create(Request $request) { abort_unless($request->user()->can('inventory.manage'), 403); return view('movements.form', ['products' => Product::where('is_active', true)->orderBy('name')->get(), 'branches' => Branch::where('is_active', true)->when($request->user()->role !== 'administrador', fn($q) => $q->whereIn('id', $request->user()->branches()->pluck('branches.id')))->orderBy('name')->get()]); }
    public function store(Request $request)
    {
        abort_unless($request->user()->can('inventory.manage'), 403);
        $data = $request->validate(['product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)], 'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)], 'type' => 'required|in:entrada,salida', 'quantity' => 'required|integer|min:1|max:2147483647', 'movement_date' => 'required|date|before_or_equal:today', 'reason' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000']);
        $data['movement_date'] = Carbon::parse($data['movement_date'])->startOfDay();
        abort_unless($request->user()->canAccessBranch((int) $data['branch_id']), 403);
        $movement = null;
        DB::transaction(function () use ($data, $request, &$movement) {
            $inventory = Inventory::where(['product_id' => $data['product_id'], 'branch_id' => $data['branch_id']])->lockForUpdate()->first();
            if (! $inventory) { $inventory = Inventory::create(['product_id' => $data['product_id'], 'branch_id' => $data['branch_id'], 'quantity' => 0]); $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->first(); }
            $before = $inventory->quantity; $after = $data['type'] === 'entrada' ? $before + $data['quantity'] : $before - $data['quantity'];
            if ($after > 2147483647) { abort(422, 'La cantidad resultante supera el límite permitido.'); }
            if ($after < 0) { abort(422, 'No hay stock suficiente para esta salida.'); }
            $inventory->update(['quantity' => $after, 'last_entry_at' => $data['type'] === 'entrada' ? $data['movement_date'] : $inventory->last_entry_at, 'exhausted_at' => $after === 0 ? $data['movement_date'] : ($data['type'] === 'entrada' ? null : $inventory->exhausted_at)]);
            $movement = InventoryMovement::create(array_merge($data, ['user_id' => $request->user()->id, 'stock_before' => $before, 'stock_after' => $after]));
        });
        $this->audit('inventory.movement.created', $movement, null, $data);
        return redirect()->route('movements.index')->with('success', 'Movimiento registrado.');
    }
}
