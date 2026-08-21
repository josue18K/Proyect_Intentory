<?php
namespace App\Http\Controllers;

use App\Models\{Branch, Inventory, InventoryMovement, Product};
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function branchIds(Request $request)
    {
        return $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $ids = $this->branchIds($request);
        $branches = Branch::where('is_active', true)->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get();
        if (! $request->filled('branch_id')) {
            return view('reports.index', ['inventory' => collect(), 'movements' => collect(), 'products' => collect(), 'branches' => $branches]);
        }
        $filter = fn ($q) => $q->when($ids, fn ($q) => $q->whereIn('branch_id', $ids))->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v));
        $inventory = Inventory::with(['product','branch'])->where($filter)->get();
        $movements = InventoryMovement::with(['product','branch'])->where($filter)->when($request->type, fn ($q, $v) => $q->where('type', $v))->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))->latest()->get();
        $products = Product::withCount(['movements as movement_count' => fn ($q) => $q->where($filter)->when($request->type, fn ($q, $v) => $q->where('type', $v))->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))])->get();
        return view('reports.index', compact('inventory', 'movements', 'branches', 'products'));
    }

    public function csv(Request $request): StreamedResponse
    {
        $ids = $this->branchIds($request);
        abort_unless($request->filled('branch_id'), 422, 'Selecciona una sede para generar el reporte.');
        $rows = InventoryMovement::with(['product','branch'])->when($ids, fn ($q) => $q->whereIn('branch_id', $ids))->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))->when($request->type, fn ($q, $v) => $q->where('type', $v))->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))->get();
        return response()->streamDownload(function () use ($rows) { $out = fopen('php://output', 'w'); fputcsv($out, ['Fecha','Producto','Sede','Tipo','Cantidad','Stock resultante']); foreach ($rows as $row) fputcsv($out, [$row->created_at, $row->product->name, $row->branch->name, $row->type, $row->quantity, $row->stock_after]); fclose($out); }, 'movimientos.csv', ['Content-Type' => 'text/csv']);
    }
}
