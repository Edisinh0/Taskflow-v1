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
        Schema::table('progress', function (Blueprint $table) {
            // Drop the 'name' column if it exists
            if (Schema::hasColumn('progress', 'name')) {
                $table->dropColumn('name');
            }

            // Drop the 'date' column if it exists
            if (Schema::hasColumn('progress', 'date')) {
                $table->dropIndex(['date']);
                $table->dropColumn('date');
            }

            // Make description required instead of nullable
            $table->text('description')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            // Restore the 'name' column
            $table->string('name')->after('task_id');

            // Restore the 'date' column
            $table->date('date')->after('description');
            $table->index('date');

            // Make description nullable again
            $table->text('description')->nullable()->change();
        });
    }
};
