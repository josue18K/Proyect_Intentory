<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });
        DB::table('products')->orderBy('id')->eachById(function ($product) {
            $branchId = DB::table('inventories')->where('product_id', $product->id)->orderBy('branch_id')->value('branch_id');
            DB::table('products')->where('id', $product->id)->update(['branch_id' => $branchId]);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['internal_code']);
            $table->dropUnique(['barcode']);
            $table->unique(['branch_id', 'internal_code']);
            $table->unique(['branch_id', 'barcode']);
        });
        Schema::create('stock_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('low_stock_count')->default(0);
            $table->unsignedInteger('empty_stock_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reviews');
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'internal_code']);
            $table->dropUnique(['branch_id', 'barcode']);
            $table->unique('internal_code');
            $table->unique('barcode');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
