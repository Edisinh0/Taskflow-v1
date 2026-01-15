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
        Schema::table('flows', function (Blueprint $table) {
            // Agregar columna responsible_id después de created_by
            $table->unsignedBigInteger('responsible_id')->nullable()->after('created_by');

            // Foreign key constraint
            $table->foreign('responsible_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); // Si se elimina el usuario, se pone en null

            // Index para performance en queries
            $table->index('responsible_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flows', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
            $table->dropIndex(['responsible_id']);
            $table->dropColumn('responsible_id');
        });
    }
};
