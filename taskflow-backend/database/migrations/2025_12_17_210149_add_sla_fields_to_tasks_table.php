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
            // Campos para SLA (Service Level Agreement)
            $table->timestamp('sla_due_date')->nullable()->after('estimated_end_at')->comment('Fecha límite de SLA');
            $table->boolean('sla_breached')->default(false)->after('sla_due_date')->comment('Indica si se ha superado el SLA');
            $table->timestamp('sla_breach_at')->nullable()->after('sla_breached')->comment('Fecha cuando se superó el SLA');
            $table->integer('sla_days_overdue')->default(0)->after('sla_breach_at')->comment('Días de retraso del SLA');
            $table->boolean('sla_notified_assignee')->default(false)->after('sla_days_overdue')->comment('Notificación enviada al responsable (+1 día)');
            $table->boolean('sla_escalated')->default(false)->after('sla_notified_assignee')->comment('Escalado al supervisor (+2 días)');
            $table->timestamp('sla_notified_at')->nullable()->after('sla_escalated')->comment('Fecha de primera notificación');
            $table->timestamp('sla_escalated_at')->nullable()->after('sla_notified_at')->comment('Fecha de escalamiento');

            // Índices para mejorar el rendimiento de las consultas
            $table->index('sla_due_date');
            $table->index('sla_breached');
            $table->index(['sla_breached', 'sla_days_overdue']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['tasks_sla_due_date_index']);
            $table->dropIndex(['tasks_sla_breached_index']);
            $table->dropIndex(['tasks_sla_breached_sla_days_overdue_index']);

            $table->dropColumn([
                'sla_due_date',
                'sla_breached',
                'sla_breach_at',
                'sla_days_overdue',
                'sla_notified_assignee',
                'sla_escalated',
                'sla_notified_at',
                'sla_escalated_at',
            ]);
        });
    }
};
