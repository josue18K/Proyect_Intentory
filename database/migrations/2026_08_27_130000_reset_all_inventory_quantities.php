<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_quantity_reset_backup', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_id')->primary();
            $table->unsignedInteger('previous_quantity');
            $table->timestamp('previous_last_entry_at')->nullable();
            $table->timestamp('previous_exhausted_at')->nullable();
            $table->timestamp('reset_at');
        });

        DB::table('inventories')->orderBy('id')->chunkById(500, function ($inventories) {
            DB::table('inventory_quantity_reset_backup')->insert(
                $inventories->map(fn ($inventory) => [
                    'inventory_id' => $inventory->id,
                    'previous_quantity' => $inventory->quantity,
                    'previous_last_entry_at' => $inventory->last_entry_at,
                    'previous_exhausted_at' => $inventory->exhausted_at,
                    'reset_at' => now(),
                ])->all()
            );
        });

        DB::table('inventories')->update([
            'quantity' => 0,
            'exhausted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('inventory_quantity_reset_backup')->orderBy('inventory_id')->chunkById(500, function ($backups) {
            foreach ($backups as $backup) {
                DB::table('inventories')->where('id', $backup->inventory_id)->update([
                    'quantity' => $backup->previous_quantity,
                    'last_entry_at' => $backup->previous_last_entry_at,
                    'exhausted_at' => $backup->previous_exhausted_at,
                    'updated_at' => now(),
                ]);
            }
        }, 'inventory_id');

        Schema::dropIfExists('inventory_quantity_reset_backup');
    }
};
