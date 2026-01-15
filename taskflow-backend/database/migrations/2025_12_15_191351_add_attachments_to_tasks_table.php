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
        // 1. Agregar flag a tabla tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('allow_attachments')->default(false)->after('is_milestone');
        });

        // 2. Crear tabla de adjuntos
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Usuario que subió el archivo
            $table->string('name'); // Nombre original del archivo (ej: reporte.pdf)
            $table->string('file_path'); // Ruta interna (ej: attachments/xyz.pdf)
            $table->string('file_type')->nullable(); // Mime type (application/pdf)
            $table->bigInteger('file_size')->default(0); // Tamaño en bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('allow_attachments');
        });
    }
};
