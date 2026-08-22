<?php
namespace App\Http\Controllers;
use App\Models\{Branch, Inventory, InventoryMovement, Product, Transfer};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class TransferController extends Controller
{
    public function index(Request $request) { abort_unless($request->user()->can('inventory.view'), 403); $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id'); $items = Transfer::with(['product','fromBranch','toBranch','user'])->when($allowed, fn($q) => $q->where(fn($q) => $q->whereIn('from_branch_id', $allowed)->orWhereIn('to_branch_id', $allowed)))->when($request->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(15)->withQueryString(); return view('transfers.index', compact('items')); }
    public function create(Request $request) { abort_unless($request->user()->can('inventory.manage'), 403); $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id'); return view('transfers.form', ['products' => Product::where('is_active', true)->when($allowed, fn ($q) => $q->whereHas('inventories', fn ($i) => $i->whereIn('branch_id', $allowed)))->orderBy('name')->get(), 'branches' => Branch::where('is_active', true)->when($allowed, fn($q) => $q->whereIn('id', $allowed))->orderBy('name')->get()]); }
    public function store(Request $request)
    {
        abort_unless($request->user()->can('inventory.manage'), 403);
        $data = $request->validate(['product_id'=>['required', Rule::exists('products', 'id')->where('is_active', true)],'from_branch_id'=>['required','different:to_branch_id',Rule::exists('branches', 'id')->where('is_active', true)],'to_branch_id'=>['required',Rule::exists('branches', 'id')->where('is_active', true)],'quantity'=>'required|integer|min:1|max:2147483647','notes'=>'nullable|string|max:5000']);
        abort_unless($request->user()->canAccessBranch((int) $data['from_branch_id']) && $request->user()->canAccessBranch((int) $data['to_branch_id']), 403);
        $transfer = DB::transaction(function () use ($data, $request) {
            Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            if (Transfer::where($data)->where('status', 'pending')->exists()) { return null; }
            return Transfer::create($data + ['user_id' => $request->user()->id]);
        });
        if (! $transfer) { return back()->withErrors(['quantity' => 'Ya existe una transferencia pendiente con los mismos datos.'])->withInput(); }
        $this->audit('transfer.created', $transfer, null, $data);
        return redirect()->route('transfers.index')->with('success', 'Transferencia creada en estado pendiente.');
    }
    public function complete(Request $request, Transfer $transfer)
    {
        abort_unless($request->user()->can('inventory.manage') && $request->user()->canAccessBranch($transfer->from_branch_id) && $request->user()->canAccessBranch($transfer->to_branch_id), 403);
        DB::transaction(function () use ($request, $transfer) {
            $transfer = Transfer::with(['product', 'fromBranch', 'toBranch'])->whereKey($transfer->id)->lockForUpdate()->first();
            abort_if($transfer->status !== 'pending', 422, 'La transferencia ya fue procesada.');
            abort_unless($transfer->product->is_active && $transfer->fromBranch->is_active && $transfer->toBranch->is_active, 422, 'El producto y las sedes deben estar activos.');
            $inventories = Inventory::where('product_id', $transfer->product_id)->whereIn('branch_id', [$transfer->from_branch_id, $transfer->to_branch_id])->orderBy('branch_id')->lockForUpdate()->get();
            $source = $inventories->firstWhere('branch_id', $transfer->from_branch_id);
            abort_unless($source && $source->quantity >= $transfer->quantity, 422, 'Stock insuficiente en la sede de origen.');
            $destination = $inventories->firstWhere('branch_id', $transfer->to_branch_id);
            if (!$destination) { $destination = Inventory::create(['product_id'=>$transfer->product_id,'branch_id'=>$transfer->to_branch_id,'quantity'=>0]); $destination = Inventory::whereKey($destination->id)->lockForUpdate()->first(); }
            $sourceBefore = $source->quantity; $destinationBefore = $destination->quantity; $source->update(['quantity'=>$sourceBefore-$transfer->quantity]); $destination->update(['quantity'=>$destinationBefore+$transfer->quantity]);
            InventoryMovement::create(['product_id'=>$transfer->product_id,'branch_id'=>$transfer->from_branch_id,'user_id'=>$request->user()->id,'type'=>'salida','quantity'=>$transfer->quantity,'stock_before'=>$sourceBefore,'stock_after'=>$sourceBefore-$transfer->quantity,'reason'=>'Transferencia #'.$transfer->id]);
            InventoryMovement::create(['product_id'=>$transfer->product_id,'branch_id'=>$transfer->to_branch_id,'user_id'=>$request->user()->id,'type'=>'entrada','quantity'=>$transfer->quantity,'stock_before'=>$destinationBefore,'stock_after'=>$destinationBefore+$transfer->quantity,'reason'=>'Transferencia #'.$transfer->id]);
            $transfer->update(['status'=>'completed','completed_at'=>now(),'completed_by'=>$request->user()->id]); $this->audit('transfer.completed', $transfer, ['status'=>'pending'], ['status'=>'completed']);
        });
        return back()->with('success', 'Transferencia completada y stock actualizado.');
    }
    public function cancel(Request $request, Transfer $transfer) { abort_unless($request->user()->can('inventory.manage') && $request->user()->canAccessBranch($transfer->from_branch_id) && $request->user()->canAccessBranch($transfer->to_branch_id), 403); DB::transaction(function () use ($transfer) { $transfer = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail(); abort_if($transfer->status !== 'pending', 422, 'La transferencia ya fue procesada.'); $transfer->update(['status'=>'cancelled']); $this->audit('transfer.cancelled', $transfer, ['status'=>'pending'], ['status'=>'cancelled']); }); return back()->with('success', 'Transferencia cancelada.'); }
}
