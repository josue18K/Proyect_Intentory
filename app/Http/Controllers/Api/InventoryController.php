<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Branch, Inventory, InventoryMovement, Product, Transfer, User, License};
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

    public function transfers(Request $request)
    {
        $allowed = $this->allowedBranches($request->user());
        $items = Transfer::with(['product', 'fromBranch', 'toBranch', 'user'])
            ->when($allowed, fn ($q) => $q->where(fn ($q) => $q->whereIn('from_branch_id', $allowed)->orWhereIn('to_branch_id', $allowed)))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(15);
        return response()->json(['success' => true, 'message' => 'Transferencias obtenidas.', 'data' => $items->items(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function storeTransfer(Request $request)
    {
        abort_unless($request->user()->can('inventory.manage'), 403);
        if ($request->filled('product_id')) {
            $productId = Product::whereKey($request->input('product_id'))
                ->orWhere('barcode', $request->input('product_id'))
                ->orWhere('internal_code', $request->input('product_id'))
                ->value('id');
            $request->merge(['product_id' => $productId ?? $request->input('product_id')]);
        }
        $data = $request->validate(['product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)], 'from_branch_id' => ['required', 'different:to_branch_id', Rule::exists('branches', 'id')->where('is_active', true)], 'to_branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)], 'quantity' => 'required|integer|min:1', 'notes' => 'nullable|string|max:5000']);
        abort_unless($request->user()->canAccessBranch((int) $data['from_branch_id']) && $request->user()->canAccessBranch((int) $data['to_branch_id']), 403, 'No tienes acceso a las sedes seleccionadas.');
        abort_unless(Inventory::where(['product_id' => $data['product_id'], 'branch_id' => $data['from_branch_id']])->exists(), 422, 'El producto no tiene inventario en la sede de origen.');
        $transfer = Transfer::create($data + ['user_id' => $request->user()->id]);
        return $this->ok($transfer->load(['product', 'fromBranch', 'toBranch', 'user']), 'Transferencia creada.', 201);
    }

    public function completeTransfer(Request $request, Transfer $transfer)
    {
        abort_unless($request->user()->can('inventory.manage') && $request->user()->canAccessBranch($transfer->from_branch_id) && $request->user()->canAccessBranch($transfer->to_branch_id), 403);
        DB::transaction(function () use ($request, $transfer) {
            $transfer = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_if($transfer->status !== 'pending', 422, 'La transferencia ya fue procesada.');
            $source = Inventory::where(['product_id' => $transfer->product_id, 'branch_id' => $transfer->from_branch_id])->lockForUpdate()->first();
            abort_unless($source && $source->quantity >= $transfer->quantity, 422, 'Stock insuficiente en la sede de origen.');
            $destination = Inventory::firstOrCreate(['product_id' => $transfer->product_id, 'branch_id' => $transfer->to_branch_id], ['quantity' => 0]);
            $destination = Inventory::whereKey($destination->id)->lockForUpdate()->first();
            $sourceBefore = $source->quantity; $destinationBefore = $destination->quantity;
            $source->update(['quantity' => $sourceBefore - $transfer->quantity]);
            abort_if($destinationBefore + $transfer->quantity > 2147483647, 422, 'La cantidad resultante supera el límite permitido.');
            $destination->update(['quantity' => $destinationBefore + $transfer->quantity]);
            foreach ([[$transfer->from_branch_id, 'salida', $sourceBefore, $source->quantity], [$transfer->to_branch_id, 'entrada', $destinationBefore, $destination->quantity]] as [$branch, $type, $before, $after]) InventoryMovement::create(['product_id' => $transfer->product_id, 'branch_id' => $branch, 'user_id' => $request->user()->id, 'type' => $type, 'quantity' => $transfer->quantity, 'stock_before' => $before, 'stock_after' => $after, 'movement_date' => today(), 'reason' => 'Transferencia #'.$transfer->id]);
            $transfer->update(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $request->user()->id]);
        });
        return $this->ok($transfer->fresh()->load(['product', 'fromBranch', 'toBranch']), 'Transferencia completada.');
    }

    public function inventoryReport(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $request->validate(['branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)]]);
        $branch = $request->filled('branch_id') ? Branch::findOrFail($request->branch_id) : null;
        if ($branch) abort_unless($request->user()->canAccessBranch($branch->id), 403);
        $inventory = Inventory::with(['product.category', 'branch'])->when($branch, fn ($q) => $q->where('branch_id', $branch->id))->when(!$branch && $this->allowedBranches($request->user()), fn ($q, $ids) => $q->whereIn('branch_id', $ids))->get();
        return $this->ok(['branch' => $branch, 'inventory' => $inventory, 'totals' => ['items' => $inventory->count(), 'units' => $inventory->sum('quantity'), 'low' => $inventory->filter(fn ($x) => $x->quantity > 0 && $x->quantity <= $x->product->minimum_stock)->count()]], 'Reporte obtenido.');
    }

    public function adminUsers() { return $this->ok(User::with('branches')->latest()->paginate(20), 'Usuarios obtenidos.'); }
    public function adminLicenses() { return $this->ok(License::with(['branch', 'creator'])->latest()->paginate(20), 'Licencias obtenidas.'); }
    public function storeLicense(Request $request) { $data = $request->validate(['branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)]]); $license = License::create(['branch_id' => $data['branch_id'], 'created_by' => $request->user()->id, 'code' => strtoupper(bin2hex(random_bytes(5)))]); return $this->ok($license->load('branch'), 'Licencia creada.', 201); }
    public function adminAudit(Request $request) { return $this->ok(AuditLog::with('user')->when($request->search, fn ($q, $v) => $q->where('action', 'like', "%{$v}%"))->latest()->paginate(20), 'Auditoría obtenida.'); }

    private function allowedBranches($user)
    {
        return $user->role === 'administrador' ? null : $user->branches()->pluck('branches.id');
    }

    private function ok($data, string $message, int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}
