<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfExportService
{
    /**
     * Generar archivo PDF
     */
    public function generate(Collection $tasks, array $filters, array $stats): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'tasks' => $tasks,
            'filters' => $this->formatFilters($filters),
            'stats' => $stats,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        $pdf = Pdf::loadView('reports.pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf;
    }

    /**
     * Formatear filtros para mostrar en el PDF
     */
    private function formatFilters(array $filters): array
    {
        $formatted = [];

        if (!empty($filters['status'])) {
            $status = is_array($filters['status']) ? implode(', ', $filters['status']) : $filters['status'];
            $formatted['Estado'] = $status;
        }

        if (!empty($filters['priority'])) {
            $priority = is_array($filters['priority']) ? implode(', ', $filters['priority']) : $filters['priority'];
            $formatted['Prioridad'] = $priority;
        }

        if (!empty($filters['assignee_id'])) {
            $formatted['Usuario ID'] = $filters['assignee_id'];
        }

        if (!empty($filters['flow_id'])) {
            $formatted['Flujo ID'] = $filters['flow_id'];
        }

        if (!empty($filters['date_from'])) {
            $formatted['Desde'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $formatted['Hasta'] = $filters['date_to'];
        }

        if (isset($filters['is_milestone'])) {
            $formatted['Solo Milestones'] = $filters['is_milestone'] ? 'Sí' : 'No';
        }

        return $formatted;
    }
}
