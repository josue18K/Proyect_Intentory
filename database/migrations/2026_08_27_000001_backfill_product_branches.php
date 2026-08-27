<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Products created before branch_id was introduced inherit their inventory branch.
        DB::table('products')->whereNull('branch_id')->orderBy('id')->eachById(function ($product) {
            $branchId = DB::table('inventories')
                ->where('product_id', $product->id)
                ->orderBy('branch_id')
                ->value('branch_id');

            if ($branchId) {
                DB::table('products')->where('id', $product->id)->update(['branch_id' => $branchId]);
            }
        });
    }

    public function down(): void
    {
        // The repair is intentionally not reversed; branch ownership is valid product data.
    }
};
