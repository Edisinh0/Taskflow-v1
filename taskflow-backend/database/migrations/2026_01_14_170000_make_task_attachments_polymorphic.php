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
        Schema::table('task_attachments', function (Blueprint $table) {
            // Hacer que la tabla sea polimórfica
            // Primero eliminar la restricción de clave foránea de task_id si es necesaria

            // Agregar columnas polimórficas
            $table->string('attachmentable_type')->default('App\Models\Task');
            $table->unsignedBigInteger('attachmentable_id')->default(0);

            // Crear índice compuesto para las relaciones polimórficas
            $table->index(['attachmentable_id', 'attachmentable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            $table->dropIndex(['attachmentable_id', 'attachmentable_type']);
            $table->dropColumn(['attachmentable_type', 'attachmentable_id']);
        });
    }
};
