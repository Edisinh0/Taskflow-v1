<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CsvExportService
{
    /**
     * Generar archivo CSV
     */
    public function generate(Collection $tasks, array $filters): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Agregar BOM para UTF-8 (compatibilidad con Excel)
        fputs($output, "\xEF\xBB\xBF");

        // Encabezado del reporte
        fputcsv($output, ['REPORTE DE TAREAS']);
        fputcsv($output, ['Generado:', now()->format('Y-m-d H:i:s')]);
        fputcsv($output, ['']);

        // Filtros aplicados
        if (!empty($filters)) {
            fputcsv($output, ['FILTROS APLICADOS:']);
            foreach ($this->formatFilters($filters) as $filter) {
                fputcsv($output, $filter);
            }
            fputcsv($output, ['']);
        }

        // Encabezados de columnas
        if ($tasks->isNotEmpty()) {
            $headers = array_keys($tasks->first());
            fputcsv($output, $headers);

            // Datos
            foreach ($tasks as $task) {
                fputcsv($output, $task);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Formatear filtros para mostrar en el CSV
     */
    private function formatFilters(array $filters): array
    {
        $formatted = [];

        if (!empty($filters['status'])) {
            $status = is_array($filters['status']) ? implode(', ', $filters['status']) : $filters['status'];
            $formatted[] = ['Estado:', $status];
        }

        if (!empty($filters['priority'])) {
            $priority = is_array($filters['priority']) ? implode(', ', $filters['priority']) : $filters['priority'];
            $formatted[] = ['Prioridad:', $priority];
        }

        if (!empty($filters['assignee_id'])) {
            $formatted[] = ['Usuario ID:', $filters['assignee_id']];
        }

        if (!empty($filters['flow_id'])) {
            $formatted[] = ['Flujo ID:', $filters['flow_id']];
        }

        if (!empty($filters['date_from'])) {
            $formatted[] = ['Desde:', $filters['date_from']];
        }

        if (!empty($filters['date_to'])) {
            $formatted[] = ['Hasta:', $filters['date_to']];
        }

        if (isset($filters['is_milestone'])) {
            $formatted[] = ['Solo Milestones:', $filters['is_milestone'] ? 'Sí' : 'No'];
        }

        return $formatted;
    }
}
