<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tareas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            background: #3B82F6;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3B82F6;
        }
        
        .filters {
            background: #F3F4F6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .filter-item {
            margin-bottom: 5px;
        }
        
        .filter-label {
            font-weight: bold;
            color: #4B5563;
        }
        
        .stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .stat-row {
            display: table-row;
        }
        
        .stat-cell {
            display: table-cell;
            padding: 8px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
        }
        
        .stat-label {
            font-weight: bold;
            color: #6B7280;
        }
        
        .stat-value {
            color: #1F2937;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th {
            background: #1F2937;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 9px;
        }
        
        tr:nth-child(even) {
            background: #F9FAFB;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-pending { background: #FEF3C7; color: #92400E; }
        .badge-in-progress { background: #DBEAFE; color: #1E40AF; }
        .badge-completed { background: #D1FAE5; color: #065F46; }
        .badge-paused { background: #FED7AA; color: #9A3412; }
        .badge-cancelled { background: #FEE2E2; color: #991B1B; }
        
        .badge-low { background: #DBEAFE; color: #1E40AF; }
        .badge-medium { background: #FEF3C7; color: #92400E; }
        .badge-high { background: #FED7AA; color: #9A3412; }
        .badge-urgent { background: #FEE2E2; color: #991B1B; }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #6B7280;
            padding: 10px 0;
            border-top: 1px solid #E5E7EB;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>📊 Reporte de Tareas</h1>
        <p>Generado el: {{ $generated_at }}</p>
    </div>

    <!-- Filtros Aplicados -->
    @if(count($filters) > 0)
    <div class="section">
        <div class="section-title">Filtros Aplicados</div>
        <div class="filters">
            @foreach($filters as $label => $value)
            <div class="filter-item">
                <span class="filter-label">{{ $label }}:</span> {{ $value }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Estadísticas -->
    <div class="section">
        <div class="section-title">Estadísticas Generales</div>
        <div class="stats">
            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-label">Total de Tareas</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Progreso Promedio</div>
                    <div class="stat-value">{{ $stats['avg_progress'] }}%</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Milestones</div>
                    <div class="stat-value">{{ $stats['milestones'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Bloqueadas</div>
                    <div class="stat-value">{{ $stats['blocked'] }}</div>
                </div>
            </div>
        </div>

        <div class="stats" style="margin-top: 10px;">
            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value">{{ $stats['by_status']['pending'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">En Progreso</div>
                    <div class="stat-value">{{ $stats['by_status']['in_progress'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Completadas</div>
                    <div class="stat-value">{{ $stats['by_status']['completed'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Pausadas</div>
                    <div class="stat-value">{{ $stats['by_status']['paused'] }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Canceladas</div>
                    <div class="stat-value">{{ $stats['by_status']['cancelled'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Tareas -->
    <div class="section">
        <div class="section-title">Detalle de Tareas ({{ count($tasks) }} registros)</div>
        
        @if(count($tasks) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 25%;">Título</th>
                    <th style="width: 10%;">Estado</th>
                    <th style="width: 10%;">Prioridad</th>
                    <th style="width: 15%;">Asignado</th>
                    <th style="width: 15%;">Flujo</th>
                    <th style="width: 10%;">Progreso</th>
                    <th style="width: 10%;">Creada</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                <tr>
                    <td>{{ $task['ID'] }}</td>
                    <td>{{ $task['Título'] }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower(str_replace(' ', '-', $task['Estado'])) }}">
                            {{ $task['Estado'] }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ strtolower($task['Prioridad']) }}">
                            {{ $task['Prioridad'] }}
                        </span>
                    </td>
                    <td>{{ $task['Asignado'] }}</td>
                    <td>{{ $task['Flujo'] }}</td>
                    <td>{{ $task['Progreso'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($task['Creada'])->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #6B7280; padding: 20px;">No se encontraron tareas con los filtros aplicados.</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Taskflow - Sistema de Gestión de Tareas | Página <span class="pagenum"></span>
    </div>
</body>
</html>
