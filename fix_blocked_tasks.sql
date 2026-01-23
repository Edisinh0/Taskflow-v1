-- Script para corregir tareas con status='blocked' (valor inválido)
-- Convertir status='blocked' a status='pending' y mantener is_blocked=true

UPDATE tasks
SET
    status = 'pending',
    is_blocked = 1,
    updated_at = NOW()
WHERE status = 'blocked';

-- Verificar resultados
SELECT
    COUNT(*) as total_blocked_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked_flag_set
FROM tasks
WHERE is_blocked = 1;
