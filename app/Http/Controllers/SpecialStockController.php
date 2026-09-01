<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\SpecialStockService;
use Illuminate\Http\Request;

class SpecialStockController extends Controller
{
    public function __invoke(Request $request, SpecialStockService $service)
    {
        abort_unless($request->user()->can('reports.view'), 403);
        $allowed = $request->user()->role === 'administrador' ? null : $request->user()->branches()->pluck('branches.id');
        $branches = Branch::where('is_active', true)
            ->when($allowed, fn ($query) => $query->whereIn('id', $allowed))
            ->orderBy('name')->get();
        $branchId = $request->integer('branch_id') ?: null;
        if ($branchId) abort_unless($branches->contains('id', $branchId), 403);

        return view('reports.special-stock', [
            'groups' => $service->groups($request->user(), $branchId),
            'branches' => $branches,
            'selectedBranch' => $branches->firstWhere('id', $branchId),
        ]);
    }
}
