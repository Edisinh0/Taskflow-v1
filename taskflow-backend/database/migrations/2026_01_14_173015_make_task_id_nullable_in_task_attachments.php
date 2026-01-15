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
            // Primero eliminar la foreign key constraint
            $table->dropForeign(['task_id']);

            // Hacer que task_id sea nullable
            $table->unsignedBigInteger('task_id')->nullable()->change();

            // Volver a agregar la foreign key pero sin cascade estricto
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_attachments', function (Blueprint $table) {
            // Volver a hacer task_id NOT NULL
            $table->dropForeign(['task_id']);
            $table->unsignedBigInteger('task_id')->nullable(false)->change();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }
};
