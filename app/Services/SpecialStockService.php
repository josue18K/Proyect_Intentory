<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class SpecialStockService
{
    public const GROUPS = [
        'chemicals' => ['title' => 'Stock de químicos', 'icon' => 'flask-conical'],
        'quick_purchases' => ['title' => 'Compras rápidas', 'icon' => 'shopping-basket'],
    ];

    public function groups(User $user, ?int $branchId = null): Collection
    {
        $allowed = $user->role === 'administrador' ? null : $user->branches()->pluck('branches.id');

        return collect(self::GROUPS)->map(function (array $meta, string $key) use ($allowed, $branchId) {
            $products = Product::query()
                ->where('report_group', $key)
                ->where('is_active', true)
                ->when($allowed, fn ($query) => $query->whereHas('inventories', fn ($inventory) => $inventory->whereIn('branch_id', $allowed)))
                ->withSum(['inventories as stock' => fn ($inventory) => $inventory
                    ->when($allowed, fn ($query) => $query->whereIn('branch_id', $allowed))
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))], 'quantity')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => $this->productData($product));

            return $meta + [
                'key' => $key,
                'products' => $products,
                'total_units' => $products->sum('quantity'),
                'message' => $this->message($meta['title'], $products),
            ];
        })->values();
    }

    private function productData(Product $product): array
    {
        $quantity = (int) ($product->stock ?? 0);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'internal_code' => $product->internal_code,
            'quantity' => $quantity,
        ];
    }

    private function message(string $title, Collection $products): string
    {
        $lines = $products->map(fn (array $product) => sprintf(
            '• %s: %d %s',
            $product['name'],
            $product['quantity'],
            $product['quantity'] === 1 ? 'unidad' : 'unidades',
        ));

        return "*".mb_strtoupper($title)."*\nActualizado: ".now()->format('d/m/Y H:i')."\n\n".$lines->implode("\n");
    }
}
