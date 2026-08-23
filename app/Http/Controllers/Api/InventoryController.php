<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Branch, Inventory, InventoryMovement, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function products(Request $request)
    {
        $user = $request->user();
        $allowed = $this->allowedBranches($user);
        $items = Product::with(['category', 'inventories' => fn ($q) => $q->when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed))])
            ->where('is_active', true)
            ->when($allowed, fn ($q) => $q->whereHas('inventories', fn ($i) => $i->whereIn('branch_id', $allowed)))
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('internal_code', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%")))
            ->orderBy('name')->get();

        return $this->ok($items, 'Productos obtenidos.');
    }

    public function dashboard(Request $request)
    {
        $allowed = $this->allowedBranches($request->user());
        $inventory = Inventory::when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed));
        $movements = InventoryMovement::when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed));

        return $this->ok([
            'stats' => [
                'products' => Product::where('is_active', true)->when($allowed, fn ($q) => $q->whereHas('inventories', fn ($i) => $i->whereIn('branch_id', $allowed)))->count(),
                'stock' => (clone $inventory)->sum('quantity'),
                'low' => (clone $inventory)->join('products', 'products.id', '=', 'inventories.product_id')->whereColumn('inventories.quantity', '<=', 'products.minimum_stock')->where('inventories.quantity', '>', 0)->count(),
                'empty' => (clone $inventory)->where('quantity', 0)->count(),
            ],
            'recent_movements' => (clone $movements)->with(['product', 'branch', 'user'])->latest()->limit(8)->get(),
        ], 'Dashboard obtenido.');
    }

    public function movements(Request $request)
    {
        $allowed = $this->allowedBranches($request->user());
        $paginator = InventoryMovement::with(['product', 'branch', 'user'])
            ->when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed))
            ->when($request->search, fn ($q, $search) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('internal_code', 'like', "%{$search}%")))
            ->when($request->branch_id, fn ($q, $branch) => $q->where('branch_id', $branch))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->latest('movement_date')->latest()->paginate(15);

        return response()->json(['success' => true, 'message' => 'Movimientos obtenidos.', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function storeMovement(Request $request)
    {
        if ($request->filled('product_id')) {
            $productId = Product::whereKey($request->input('product_id'))->orWhere('barcode', $request->input('product_id'))->value('id');
            $request->merge(['product_id' => $productId ?? $request->input('product_id')]);
        }
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'type' => ['required', 'in:entrada,salida'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'movement_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $user = $request->user();
        abort_unless($user->canAccessBranch((int) $data['branch_id']), 403, 'No tienes acceso a esta sede.');
        $product = Product::whereKey($data['product_id'])->where('is_active', true)->firstOrFail();
        abort_unless($user->role === 'administrador' || (int) $product->branch_id === (int) $data['branch_id'] || $product->inventories()->where('branch_id', $data['branch_id'])->exists(), 422, 'El producto no pertenece a la sede indicada.');
        $data['movement_date'] = Carbon::parse($data['movement_date'])->startOfDay();

        $movement = DB::transaction(function () use ($data, $user) {
            $inventory = Inventory::where(['product_id' => $data['product_id'], 'branch_id' => $data['branch_id']])->lockForUpdate()->first();
            if (! $inventory) {
                $inventory = Inventory::create(['product_id' => $data['product_id'], 'branch_id' => $data['branch_id'], 'quantity' => 0]);
                $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->first();
            }
            $before = $inventory->quantity;
            $after = $data['type'] === 'entrada' ? $before + $data['quantity'] : $before - $data['quantity'];
            abort_if($after > 2147483647, 422, 'La cantidad resultante supera el límite permitido.');
            abort_if($after < 0, 422, 'No hay stock suficiente para esta salida.');
            $inventory->update(['quantity' => $after, 'last_entry_at' => $data['type'] === 'entrada' ? $data['movement_date'] : $inventory->last_entry_at, 'exhausted_at' => $after === 0 ? $data['movement_date'] : ($data['type'] === 'entrada' ? null : $inventory->exhausted_at)]);
            $movement = InventoryMovement::create(array_merge($data, ['user_id' => $user->id, 'stock_before' => $before, 'stock_after' => $after]));
            AuditLog::create(['user_id' => $user->id, 'action' => 'inventory.movement.created', 'auditable_type' => InventoryMovement::class, 'auditable_id' => $movement->id, 'new_values' => $movement->toArray()]);
            return $movement;
        });

        return $this->ok($movement->load(['product', 'branch', 'user']), 'Movimiento registrado.', 201);
    }

    public function branches(Request $request)
    {
        return $this->ok(Branch::where('is_active', true)->when($this->allowedBranches($request->user()), fn ($q, $ids) => $q->whereIn('id', $ids))->orderBy('name')->get(), 'Sedes obtenidas.');
    }

    private function allowedBranches($user)
    {
        return $user->role === 'administrador' ? null : $user->branches()->pluck('branches.id');
    }

    private function ok($data, string $message, int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}
