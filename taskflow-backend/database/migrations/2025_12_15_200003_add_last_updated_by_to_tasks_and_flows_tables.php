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
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('flows', function (Blueprint $table) {
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['last_updated_by']);
            $table->dropColumn('last_updated_by');
        });

        Schema::table('flows', function (Blueprint $table) {
            $table->dropForeign(['last_updated_by']);
            $table->dropColumn('last_updated_by');
        });
    }
};
