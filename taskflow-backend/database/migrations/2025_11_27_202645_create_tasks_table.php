<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('flow_id')->constrained('flows')->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'blocked', 'in_progress', 'paused', 'completed', 'cancelled'])->default('pending');
            $table->boolean('is_milestone')->default(false);
            $table->boolean('milestone_auto_complete')->default(false);
            $table->boolean('milestone_requires_validation')->default(false);
            $table->foreignId('milestone_validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('milestone_target_date')->nullable();
            $table->integer('order')->default(0);
            $table->timestamp('estimated_start_at')->nullable();
            $table->timestamp('estimated_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->integer('progress')->default(0);
            $table->text('blocked_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('flow_id');
            $table->index('parent_task_id');
            $table->index('assignee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};