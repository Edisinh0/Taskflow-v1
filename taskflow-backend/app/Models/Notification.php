<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'flow_id',
        'type',
        'title',
        'message',
        'priority',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para obtener solo notificaciones no leídas.
     * Uso: ->unread()
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at')->where('is_read', false);
    }

    /**
     * Scope para filtrar por tipo de notificación.
     * Uso: ->ofType('sla_warning')
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
    
    /**
     * Scope para filtrar por prioridad urgente.
     * Uso: ->urgent()
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', 'urgent');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS Y RELACIONES
    |--------------------------------------------------------------------------
    */
    
    /**
     * Marcar la notificación como leída.
     * Uso: $notification->markAsRead()
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
    
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
    
    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }
}