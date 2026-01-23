<x-mail::message>
# 🚨 Escalación SLA - Atención Requerida

Hola **{{ $supervisor->name }}**,

La siguiente tarea ha superado el SLA establecido y requiere tu atención inmediata como supervisor.

## Detalles de la Tarea

**Título:** {{ $task->title }}

**Flujo:** {{ $flow->name ?? 'Sin flujo' }}

**Asignado a:** {{ $assignee->name ?? 'Sin asignar' }} ({{ $assignee->email ?? '' }})

**Prioridad:** {{ ucfirst($task->priority) }}

**Estado:** {{ ucfirst($task->status) }}

**Días de atraso:** **{{ $daysOverdue }} días**

**Fecha de vencimiento:** {{ $task->sla_due_date ? $task->sla_due_date->format('d/m/Y H:i') : 'No definida' }}

---

## Descripción

{{ $task->description ?? 'Sin descripción' }}

---

## Notas Adicionales

@if($task->notes)
{{ $task->notes }}
@else
Sin notas adicionales
@endif

---

<x-mail::button :url="$taskUrl" color="error">
Ver Tarea en Taskflow
</x-mail::button>

## Acciones Recomendadas

- Revisar el estado actual de la tarea
- Contactar al responsable asignado
- Evaluar si se requieren recursos adicionales
- Considerar reasignar la tarea si es necesario
- Actualizar el SLA si las circunstancias lo ameritan

---

<small>
Este es un correo automático generado por el Sistema de Alertas SLA de Taskflow.<br>
Si tienes preguntas sobre esta escalación, contacta al equipo de Project Management.
</small>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
