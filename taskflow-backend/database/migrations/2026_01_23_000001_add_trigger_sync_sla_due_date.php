<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea un trigger MySQL que sincroniza automáticamente sla_due_date con estimated_end_at
     * cuando se actualiza una tarea.
     *
     * Este trigger es necesario porque:
     * 1. TaskObserver solo se ejecuta con métodos Eloquent ($task->save())
     * 2. UPDATE directo a BD no dispara Observers
     * 3. Garantiza consistencia de datos en TODOS los casos
     */
    public function up(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS sync_sla_due_date_before_update;

            CREATE TRIGGER sync_sla_due_date_before_update
            BEFORE UPDATE ON tasks
            FOR EACH ROW
            BEGIN
                -- Si estimated_end_at cambió, sincronizar sla_due_date
                IF NEW.estimated_end_at IS NOT NULL AND (OLD.estimated_end_at IS NULL OR NEW.estimated_end_at != OLD.estimated_end_at) THEN
                    SET NEW.sla_due_date = NEW.estimated_end_at;
                END IF;
            END
        ');

        DB::unprepared('
            DROP TRIGGER IF EXISTS sync_sla_due_date_before_insert;

            CREATE TRIGGER sync_sla_due_date_before_insert
            BEFORE INSERT ON tasks
            FOR EACH ROW
            BEGIN
                -- Si no hay sla_due_date pero sí estimated_end_at, asignar
                IF NEW.sla_due_date IS NULL AND NEW.estimated_end_at IS NOT NULL THEN
                    SET NEW.sla_due_date = NEW.estimated_end_at;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS sync_sla_due_date_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS sync_sla_due_date_before_insert');
    }
};
