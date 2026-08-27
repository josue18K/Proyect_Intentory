<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_control_reset_backup', function (Blueprint $table) {
            $table->id();
            $table->string('record_type');
            $table->unsignedBigInteger('original_id');
            $table->json('payload');
            $table->timestamp('reset_at');
            $table->unique(['record_type', 'original_id']);
        });

        foreach (['inventory_movements', 'stock_reviews'] as $table) {
            DB::table($table)->orderBy('id')->chunkById(250, function ($records) use ($table) {
                DB::table('stock_control_reset_backup')->insert($records->map(fn ($record) => [
                    'record_type' => $table,
                    'original_id' => $record->id,
                    'payload' => json_encode((array) $record, JSON_UNESCAPED_UNICODE),
                    'reset_at' => now(),
                ])->all());
            });
        }

        DB::table('inventory_movements')->delete();
        DB::table('stock_reviews')->delete();
    }

    public function down(): void
    {
        foreach (['inventory_movements', 'stock_reviews'] as $table) {
            DB::table('stock_control_reset_backup')->where('record_type', $table)->orderBy('original_id')->chunkById(250, function ($backups) use ($table) {
                foreach ($backups as $backup) {
                    DB::table($table)->insertOrIgnore(json_decode($backup->payload, true));
                }
            });
        }

        Schema::dropIfExists('stock_control_reset_backup');
    }
};
