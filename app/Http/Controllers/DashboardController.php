<?php

namespace App\Http\Controllers;

use App\Models\{Inventory, InventoryMovement, Product};
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'products' => Product::where('is_active', true)->count(),
            'stock' => Inventory::sum('quantity'),
            'low' => Inventory::join('products', 'products.id', '=', 'inventories.product_id')->whereColumn('inventories.quantity', '<=', 'products.minimum_stock')->where('inventories.quantity', '>', 0)->count(),
            'empty' => Inventory::where('quantity', 0)->count(),
        ];
        $movements = InventoryMovement::with(['product', 'branch'])->latest()->take(8)->get();
        return view('dashboard', compact('stats', 'movements'));
    }
}
