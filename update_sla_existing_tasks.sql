-- =========================================================================
-- Script de Migración: Auto-asignar sla_due_date a Tareas Existentes
-- =========================================================================
-- Propósito: Actualizar tareas existentes que tienen estimated_end_at
--            pero NO tienen sla_due_date asignado.
--
-- Contexto: Antes de este fix, las tareas creadas desde el frontend
--           no tenían sla_due_date, por lo que el sistema SLA no podía
--           detectar atrasos ni enviar notificaciones.
--
-- Fecha: 2026-01-21
-- Sistema: Taskflow v1
-- =========================================================================

USE taskflow_db;

-- Paso 1: Ver cuántas tareas se van a actualizar
SELECT
  'Tareas a actualizar' as descripcion,
  COUNT(*) as cantidad
FROM tasks
WHERE sla_due_date IS NULL
  AND estimated_end_at IS NOT NULL
  AND status NOT IN ('completed', 'cancelled', 'deleted');

-- Paso 2: Ver detalles de las tareas que se actualizarán
SELECT
  id,
  title,
  status,
  priority,
  assignee_id,
  estimated_end_at,
  sla_due_date
FROM tasks
WHERE sla_due_date IS NULL
  AND estimated_end_at IS NOT NULL
  AND status NOT IN ('completed', 'cancelled', 'deleted')
ORDER BY estimated_end_at ASC
LIMIT 10;

-- Paso 3: ACTUALIZAR tareas existentes
-- IMPORTANTE: Esto asigna sla_due_date = estimated_end_at
UPDATE tasks
SET sla_due_date = estimated_end_at,
    updated_at = NOW()
WHERE sla_due_date IS NULL
  AND estimated_end_at IS NOT NULL
  AND status NOT IN ('completed', 'cancelled', 'deleted');

-- Paso 4: Verificar el resultado
SELECT
  'Tareas actualizadas' as resultado,
  COUNT(*) as cantidad
FROM tasks
WHERE sla_due_date IS NOT NULL
  AND sla_due_date = estimated_end_at
  AND status NOT IN ('completed', 'cancelled', 'deleted');

-- Paso 5: Ver tareas con SLA que ahora están atrasadas
SELECT
  id,
  title,
  status,
  priority,
  assignee_id,
  estimated_end_at,
  sla_due_date,
  CASE
    WHEN sla_due_date < NOW() THEN 'ATRASADA'
    ELSE 'EN TIEMPO'
  END as estado_sla,
  TIMESTAMPDIFF(DAY, sla_due_date, NOW()) as dias_atraso
FROM tasks
WHERE sla_due_date IS NOT NULL
  AND status NOT IN ('completed', 'cancelled', 'deleted')
  AND sla_due_date < NOW()
ORDER BY sla_due_date ASC
LIMIT 20;

-- =========================================================================
-- IMPORTANTE: Después de ejecutar este script, se debe ejecutar:
--   docker exec taskflow_backend_new php artisan sla:check --details
--
-- Esto procesará todas las tareas atrasadas y creará las notificaciones
-- correspondientes (warnings y escalations).
-- =========================================================================
