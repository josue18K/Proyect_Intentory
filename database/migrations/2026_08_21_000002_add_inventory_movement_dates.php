<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->timestamp('last_entry_at')->nullable()->after('quantity');
            $table->timestamp('exhausted_at')->nullable()->after('last_entry_at');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->timestamp('movement_date')->nullable()->after('stock_after');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['last_entry_at', 'exhausted_at']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('movement_date');
        });
    }
};
