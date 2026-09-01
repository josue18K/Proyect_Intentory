<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Branch, Category, Inventory, InventoryMovement, Product, Transfer, User, License};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\SpecialStockService;

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

    public function categories()
    {
        return $this->ok(Category::where('is_active', true)->orderBy('name')->get(), 'Categorías obtenidas.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name']]);
        $category = Category::create(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'is_active' => true]);
        $this->auditApi($request, 'category.created', $category);
        return $this->ok($category, 'Categoría creada.', 201);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)], 'is_active' => 'boolean']);
        $old = $category->toArray();
        $category->update(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'is_active' => $data['is_active'] ?? true]);
        $this->auditApi($request, 'category.updated', $category, $old);
        return $this->ok($category->fresh(), 'Categoría actualizada.');
    }

    public function deleteCategory(Request $request, Category $category)
    {
        $category->update(['is_active' => false]);
        $this->auditApi($request, 'category.deleted', $category);
        return $this->ok(null, 'Categoría desactivada.');
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate(['branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)], 'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)], 'internal_code' => ['nullable','max:255',Rule::unique('products')->where(fn($q)=>$q->where('branch_id',$request->branch_id))], 'barcode' => ['nullable','max:255',Rule::unique('products')->where(fn($q)=>$q->where('branch_id',$request->branch_id))], 'name' => 'required|string|max:255', 'sale_price' => 'required|numeric|min:0', 'minimum_stock' => 'required|integer|min:0', 'initial_quantity' => 'required|integer|min:0']);
        if (blank($data['internal_code'] ?? null)) {
            $prefix = strtoupper(substr(Branch::findOrFail($data['branch_id'])->slug, 0, 4));
            $next = Product::where('branch_id', $data['branch_id'])->count() + 1;
            do { $data['internal_code'] = $prefix.'-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT); }
            while (Product::where('branch_id', $data['branch_id'])->where('internal_code', $data['internal_code'])->exists());
        }
        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data + ['purchase_price' => 0, 'is_active' => true]);
            Inventory::create(['product_id' => $product->id, 'branch_id' => $data['branch_id'], 'quantity' => $data['initial_quantity']]);
            if ($data['initial_quantity'] > 0) InventoryMovement::create(['product_id' => $product->id, 'branch_id' => $data['branch_id'], 'user_id' => $request->user()->id, 'type' => 'entrada', 'quantity' => $data['initial_quantity'], 'stock_before' => 0, 'stock_after' => $data['initial_quantity'], 'movement_date' => today(), 'reason' => 'Carga inicial']);
            return $product;
        });
        $this->auditApi($request, 'product.created', $product);
        return $this->ok($product->load(['category', 'inventories']), 'Producto creado.', 201);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $data = $request->validate(['category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)], 'internal_code' => ['nullable','max:255',Rule::unique('products')->where(fn($q)=>$q->where('branch_id',$product->branch_id))->ignore($product->id)], 'barcode' => ['nullable','max:255',Rule::unique('products')->where(fn($q)=>$q->where('branch_id',$product->branch_id))->ignore($product->id)], 'name' => 'required|string|max:255', 'sale_price' => 'required|numeric|min:0', 'minimum_stock' => 'required|integer|min:0', 'current_quantity' => 'required|integer|min:0|max:2147483647']);
        $old = $product->toArray();
        DB::transaction(function () use ($data, $product, $request) {
            $product->update(collect($data)->except('current_quantity')->all());
            $inventory = Inventory::where('product_id', $product->id)->firstOrFail();
            $before = (int) $inventory->quantity;
            $after = (int) $data['current_quantity'];
            if ($before !== $after) {
                $type = $after > $before ? 'entrada' : 'salida';
                $quantity = abs($after - $before);
                $inventory->update(['quantity' => $after, 'last_entry_at' => $type === 'entrada' ? now() : $inventory->last_entry_at, 'exhausted_at' => $after === 0 ? now() : null]);
                InventoryMovement::create(['product_id' => $product->id, 'branch_id' => $inventory->branch_id, 'user_id' => $request->user()->id, 'type' => $type, 'quantity' => $quantity, 'stock_before' => $before, 'stock_after' => $after, 'movement_date' => today(), 'reason' => 'Ajuste desde aplicación']);
            }
        });
        $this->auditApi($request, 'product.updated', $product, $old);
        return $this->ok($product->fresh()->load(['category', 'inventories']), 'Producto actualizado.');
    }

    public function deleteProduct(Request $request, Product $product)
    {
        $product->update(['is_active' => false]);
        $this->auditApi($request, 'product.deleted', $product);
        return $this->ok(null, 'Producto desactivado.');
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

    public function productHistory(Request $request, Product $product)
    {
        $allowed = $this->allowedBranches($request->user());
        return $this->ok($product->movements()->with(['branch', 'user'])->when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed))->latest('movement_date')->latest()->get(), 'Historial obtenido.');
    }

    public function stockReview(Request $request)
    {
        $data = $request->validate(['branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)], 'notes' => 'nullable|string|max:1000']);
        abort_unless($request->user()->canAccessBranch((int) $data['branch_id']), 403);
        $base = Inventory::where('branch_id', $data['branch_id'])->with('product')->get();
        $review = \App\Models\StockReview::create(['branch_id' => $data['branch_id'], 'user_id' => $request->user()->id, 'low_stock_count' => $base->filter(fn ($i) => $i->quantity > 0 && $i->quantity <= $i->product->minimum_stock)->count(), 'empty_stock_count' => $base->where('quantity', 0)->count(), 'notes' => $data['notes'] ?? null]);
        return $this->ok($review, 'Revisión registrada.', 201);
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

    public function storeBranch(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:branches,name']);
        $branch = Branch::create(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'is_active' => true]);
        $this->auditApi($request, 'branch.created', $branch);
        return $this->ok($branch, 'Sede creada.', 201);
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $data = $request->validate(['name' => ['required','string','max:255',Rule::unique('branches','name')->ignore($branch->id)]]);
        $old = $branch->toArray();
        $branch->update(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);
        $this->auditApi($request, 'branch.updated', $branch, $old);
        return $this->ok($branch->fresh(), 'Sede actualizada.');
    }

    public function deleteBranch(Request $request, Branch $branch)
    {
        $old = $branch->toArray();
        $branch->update(['is_active' => false]);
        $this->auditApi($request, 'branch.deleted', $branch, $old);
        return $this->ok(null, 'Sede desactivada.');
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
        $request->validate(['branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)], 'product_ids' => 'nullable|string']);
        $branch = $request->filled('branch_id') ? Branch::findOrFail($request->branch_id) : null;
        if ($branch) abort_unless($request->user()->canAccessBranch($branch->id), 403);
        $productIds = collect(explode(',', (string) $request->input('product_ids')))->filter()->map(fn ($id) => (int) $id)->all();
        $inventory = Inventory::with(['product.category', 'branch'])->when($branch, fn ($q) => $q->where('branch_id', $branch->id))->when(!$branch && $this->allowedBranches($request->user()), fn ($q, $ids) => $q->whereIn('branch_id', $ids))->when($productIds, fn ($q) => $q->whereIn('product_id', $productIds))->get();
        return $this->ok(['branch' => $branch, 'inventory' => $inventory, 'totals' => ['items' => $inventory->count(), 'units' => $inventory->sum('quantity'), 'low' => $inventory->filter(fn ($x) => $x->quantity > 0 && $x->quantity <= $x->product->minimum_stock)->count()]], 'Reporte obtenido.');
    }

    public function specialStockReport(Request $request, SpecialStockService $service)
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $request->validate(['branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)]]);
        $branchId = $request->integer('branch_id') ?: null;
        if ($branchId) abort_unless($request->user()->canAccessBranch($branchId), 403);

        return $this->ok($service->groups($request->user(), $branchId), 'Listas especiales obtenidas.');
    }

    public function adminUsers() { return $this->ok(User::with('branches')->latest()->paginate(20), 'Usuarios obtenidos.'); }
    public function storeUser(Request $request) { $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8', 'role' => 'required|in:administrador,vendedor', 'branch_id' => 'nullable|exists:branches,id']); $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => $data['role'], 'permissions' => $data['role'] === 'administrador' ? [] : ['inventory.view', 'inventory.manage', 'reports.view']]); if ($data['role'] === 'vendedor' && !empty($data['branch_id'])) $user->branches()->sync([$data['branch_id']]); return $this->ok($user->load('branches'), 'Usuario creado.', 201); }
    public function updateUser(Request $request, User $user) { $data = $request->validate(['name' => 'sometimes|required|string|max:255', 'email' => ['sometimes','required','email',Rule::unique('users','email')->ignore($user->id)], 'role' => 'required|in:administrador,vendedor', 'permissions' => 'array', 'permissions.*' => 'in:inventory.view,inventory.manage,reports.view', 'branch_id' => 'nullable|exists:branches,id']); $old=$user->toArray(); $user->update(['name'=>$data['name']??$user->name,'email'=>$data['email']??$user->email,'role' => $data['role'], 'permissions' => $data['role'] === 'administrador' ? [] : ($data['permissions'] ?? [])]); $user->branches()->sync($data['role'] === 'administrador' ? [] : (empty($data['branch_id']) ? [] : [$data['branch_id']])); $this->auditApi($request,'user.updated',$user,$old); return $this->ok($user->fresh()->load('branches'), 'Usuario actualizado.'); }
    public function toggleUser(Request $request, User $user) { abort_if($user->is($request->user()), 422, 'No puedes desactivar tu propio usuario.'); $user->update(['is_active' => !$user->is_active]); return $this->ok($user->fresh(), 'Estado actualizado.'); }
    public function deleteUser(Request $request, User $user) { abort_if($user->is($request->user()),422,'No puedes eliminar tu propio usuario.'); $data=$request->validate(['current_password'=>'required|string']); abort_unless(Hash::check($data['current_password'],$request->user()->password),422,'La contraseña de administrador es incorrecta.'); $old=$user->toArray(); $this->auditApi($request,'user.deleted',$user,$old); $user->delete(); return $this->ok(null,'Usuario eliminado.'); }
    public function adminLicenses() { return $this->ok(License::with(['branch', 'creator'])->latest()->paginate(20), 'Licencias obtenidas.'); }
    public function storeLicense(Request $request) { $data = $request->validate(['branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)]]); $license = License::create(['branch_id' => $data['branch_id'], 'created_by' => $request->user()->id, 'code' => strtoupper(bin2hex(random_bytes(5)))]); return $this->ok($license->load('branch'), 'Licencia creada.', 201); }
    public function adminAudit(Request $request) { $items=AuditLog::with('user')->when($request->search, fn ($q, $v) => $q->where('action', 'like', "%{$v}%"))->latest()->paginate(20);$items->getCollection()->transform(function($item){$item->setAttribute('action_label',$item->actionLabel());$item->setAttribute('subject_label',$item->subjectLabel());$item->setAttribute('changes',$item->readableChanges());return $item;});return $this->ok($items, 'Auditoría obtenida.'); }

    private function allowedBranches($user)
    {
        return $user->role === 'administrador' ? null : $user->branches()->pluck('branches.id');
    }

    private function ok($data, string $message, int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function auditApi(Request $request, string $action, $model, ?array $old = null): void
    {
        AuditLog::create(['user_id'=>$request->user()->id,'action'=>$action,'auditable_type'=>$model::class,'auditable_id'=>$model->id,'old_values'=>$old,'new_values'=>$model->fresh()?->toArray()]);
    }
}
