<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        // Campo de control: Las tareas SIN dependencias se crean desbloqueadas
        $table->boolean('is_blocked')
              ->default(false) // ← CAMBIAR A false por defecto
              ->after('status')
              ->comment('Indica si la tarea está bloqueada por dependencia.');
        
        $table->foreignId('depends_on_task_id')
              ->nullable()
              ->after('is_milestone')
              ->constrained('tasks')
              ->onDelete('set null');
        
        $table->foreignId('depends_on_milestone_id')
              ->nullable()
              ->after('depends_on_task_id')
              ->constrained('tasks')
              ->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Eliminar las claves foráneas antes que las columnas.
            $table->dropForeign(['depends_on_task_id']);
            $table->dropForeign(['depends_on_milestone_id']);
            
            $table->dropColumn(['is_blocked', 'depends_on_task_id', 'depends_on_milestone_id']);
        });
    }
};