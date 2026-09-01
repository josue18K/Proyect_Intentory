<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('report_group', 40)->nullable()->after('minimum_stock')->index();
        });

        DB::table('products')->whereIn('internal_code', config('special_stock.chemicals'))
            ->update(['report_group' => 'chemicals']);
        DB::table('products')->whereIn('internal_code', config('special_stock.quick_purchases'))
            ->update(['report_group' => 'quick_purchases']);
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('report_group'));
    }

};
