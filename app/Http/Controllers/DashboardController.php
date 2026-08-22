<?php

namespace App\Http\Controllers;

use App\Models\{Inventory, InventoryMovement, Product};
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $allowed = auth()->user()->role === 'administrador' ? null : auth()->user()->branches()->pluck('branches.id');
        $inventory = Inventory::when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed));
        $stats = ['products' => Product::where('is_active', true)->when($allowed, fn ($q) => $q->whereHas('inventories', fn ($i) => $i->whereIn('branch_id', $allowed)))->count(), 'stock' => (clone $inventory)->sum('quantity'), 'low' => (clone $inventory)->join('products', 'products.id', '=', 'inventories.product_id')->whereColumn('inventories.quantity', '<=', 'products.minimum_stock')->where('inventories.quantity', '>', 0)->count(), 'empty' => (clone $inventory)->where('quantity', 0)->count()];
        $movements = InventoryMovement::with(['product', 'branch'])->when($allowed, fn ($q) => $q->whereIn('branch_id', $allowed))->latest()->take(8)->get();
        return view('dashboard', compact('stats', 'movements'));
    }
}
