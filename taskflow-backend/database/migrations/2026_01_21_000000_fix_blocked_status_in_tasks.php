<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar todas las tareas que tienen status = 'blocked' a 'pending'
        // y asegurar que is_blocked = true
        DB::table('tasks')
            ->where('status', 'blocked')
            ->update([
                'status' => 'pending',
                'is_blocked' => true,
                'updated_at' => now()
            ]);

        // Log para debugging
        $count = DB::table('tasks')->where('is_blocked', true)->count();
        \Log::info("✅ Migración completada: {$count} tareas con is_blocked=true y status corregido");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir - mantener los datos correctos
    }
};
