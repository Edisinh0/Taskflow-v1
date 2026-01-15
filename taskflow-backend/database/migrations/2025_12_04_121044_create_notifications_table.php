<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('flow_id')->nullable()->constrained('flows')->onDelete('cascade');
            
            $table->string('type'); // 'sla_warning', 'task_overdue', 'task_completed', 'task_assigned'
            $table->string('title');
            $table->text('message');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->json('data')->nullable(); // Datos adicionales
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });

        // Tabla para configuración de SLA
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('cascade');
            $table->foreignId('flow_id')->nullable()->constrained('flows')->onDelete('cascade');
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->integer('warning_hours')->default(24); // Horas antes de alertar
            $table->integer('critical_hours')->default(48); // Horas antes de marcar crítico
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('sla_rules');
    }
};