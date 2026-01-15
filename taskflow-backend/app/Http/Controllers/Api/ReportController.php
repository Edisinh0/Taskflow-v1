<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\CsvExportService;
use App\Services\PdfExportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;
    protected $csvService;
    protected $pdfService;

    public function __construct(
        ReportService $reportService,
        CsvExportService $csvService,
        PdfExportService $pdfService
    ) {
        $this->reportService = $reportService;
        $this->csvService = $csvService;
        $this->pdfService = $pdfService;
    }

    /**
     * Obtener reporte con filtros
     * GET /api/v1/reports
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status',
            'assignee_id',
            'priority',
            'flow_id',
            'is_milestone',
            'date_from',
            'date_to'
        ]);

        $query = $this->reportService->buildQuery($filters);
        
        // Paginación
        $perPage = $request->get('per_page', 50);
        $tasks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }

    /**
     * Obtener estadísticas del reporte
     * GET /api/v1/reports/stats
     */
    public function stats(Request $request)
    {
        $filters = $request->only([
            'status',
            'assignee_id',
            'priority',
            'flow_id',
            'is_milestone',
            'date_from',
            'date_to'
        ]);

        $stats = $this->reportService->getStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Exportar reporte a CSV
     * GET /api/v1/reports/export/csv
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only([
            'status',
            'assignee_id',
            'priority',
            'flow_id',
            'is_milestone',
            'date_from',
            'date_to'
        ]);

        $query = $this->reportService->buildQuery($filters);
        
        // Limitar a 10,000 registros para evitar problemas de memoria
        $tasks = $query->limit(10000)->get();
        
        // Formatear para exportación
        $formattedTasks = collect($this->reportService->formatForExport($tasks));
        
        // Generar CSV
        $csv = $this->csvService->generate($formattedTasks, $filters);
        
        $filename = 'reporte_tareas_' . now()->format('Y-m-d_His') . '.csv';
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Exportar reporte a PDF
     * GET /api/v1/reports/export/pdf
     */
    public function exportPdf(Request $request)
    {
        $filters = $request->only([
            'status',
            'assignee_id',
            'priority',
            'flow_id',
            'is_milestone',
            'date_from',
            'date_to'
        ]);

        $query = $this->reportService->buildQuery($filters);
        
        // Limitar a 1,000 registros para PDF (mejor rendimiento)
        $tasks = $query->limit(1000)->get();
        
        // Formatear para exportación
        $formattedTasks = $this->reportService->formatForExport($tasks);
        
        // Obtener estadísticas
        $stats = $this->reportService->getStats($filters);
        
        // Generar PDF
        $pdf = $this->pdfService->generate(collect($formattedTasks), $filters, $stats);
        
        $filename = 'reporte_tareas_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }
}
