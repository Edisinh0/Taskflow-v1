# 🕐 Programación del Comando SLA Check

## Configuración Actual

El comando `sla:check` está programado para ejecutarse **cada hora** de forma automática.

### Ubicación
El scheduler está configurado en: [`routes/console.php`](routes/console.php)

```php
Schedule::command('sla:check')
    ->hourly()
    ->withoutOverlapping() // Evita ejecuciones simultáneas
    ->runInBackground(); // Ejecuta en segundo plano
```

## ¿Cómo funciona?

### Ejecución Automática
El comando se ejecuta automáticamente cada hora cuando el scheduler de Laravel está activo.

### Características
- ✅ **Cada hora**: Verifica todas las tareas pendientes o en progreso
- ✅ **Sin solapamiento**: `withoutOverlapping()` previene ejecuciones simultáneas
- ✅ **En segundo plano**: No bloquea otros procesos
- ✅ **Notificaciones inteligentes**: Solo envía notificaciones una vez cada 24 horas por tarea

## Activar el Scheduler

### Opción 1: Desarrollo Local

```bash
# Ejecutar el scheduler manualmente
php artisan schedule:work

# O ejecutar el comando directamente
php artisan sla:check
```

### Opción 2: Producción con Cron

Agregar esta línea al crontab del servidor:

```bash
# Editar crontab
crontab -e

# Agregar esta línea (reemplaza /ruta/a/tu/proyecto)
* * * * * cd /ruta/a/tu/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Opción 3: Docker

Si usas Docker, asegúrate de tener un contenedor con el scheduler activo:

```dockerfile
# En tu Dockerfile o docker-compose.yml
CMD ["php", "artisan", "schedule:work"]
```

## Cambiar la Frecuencia

Puedes modificar la frecuencia de ejecución en `routes/console.php`:

```php
// Opciones disponibles:
->everyMinute()           // Cada minuto
->everyFiveMinutes()      // Cada 5 minutos
->everyTenMinutes()       // Cada 10 minutos
->everyFifteenMinutes()   // Cada 15 minutos
->everyThirtyMinutes()    // Cada 30 minutos
->hourly()                // Cada hora (actual)
->daily()                 // Una vez al día
->dailyAt('13:00')        // Todos los días a las 13:00
->twiceDaily(1, 13)       // Dos veces al día (1am y 1pm)
->weekdays()              // Solo días laborables
->weekends()              // Solo fines de semana
->mondays()               // Solo lunes
->cron('0 */2 * * *')     // Expresión cron personalizada
```

## Verificar Programación

```bash
# Ver todas las tareas programadas
php artisan schedule:list

# Ver el próximo comando a ejecutar
php artisan schedule:test
```

## Logs

El comando genera logs con información sobre:
- Tareas verificadas
- Advertencias enviadas
- Notificaciones de vencimiento

Los logs se pueden ver en:
- Laravel Logs: `storage/logs/laravel.log`
- Salida del scheduler: Si usas `schedule:work`

## Ejemplo de Salida

```
🔍 Iniciando verificación de SLA de tareas...

📋 Tareas a verificar: 15

  🚨 Tarea vencida: [42] Actualizar documentación (hace 3 horas)
  ⚠️  Advertencia: [58] Revisar código (vence en 18 horas)

✅ Verificación completada:
   - Tareas verificadas: 15
   - Advertencias enviadas: 1
   - Notificaciones de vencimiento: 1
```

## Recomendaciones

### Para Desarrollo
```bash
# Ejecutar manualmente cuando sea necesario
php artisan sla:check

# O mantener el scheduler activo en una terminal
php artisan schedule:work
```

### Para Producción
- Usar cron job para ejecutar `schedule:run` cada minuto
- Monitorear logs regularmente
- Considerar usar servicios como Laravel Horizon para procesos en segundo plano
- Configurar alertas si el scheduler falla

## Troubleshooting

### El scheduler no se ejecuta
1. Verificar que el cron job está activo: `crontab -l`
2. Verificar permisos del usuario del cron
3. Revisar logs: `tail -f storage/logs/laravel.log`

### Las notificaciones no se envían
1. Verificar que las tareas tienen `assignee_id` definido
2. Verificar que las tareas tienen `estimated_end_at` definido
3. Ejecutar manualmente: `php artisan sla:check`
4. Revisar la tabla `notifications` en la base de datos

### Muchas notificaciones duplicadas
- El comando ya tiene protección anti-duplicados (24 horas)
- Verificar que `withoutOverlapping()` está en la configuración
- Reducir frecuencia de ejecución si es necesario

## Soporte

Para más información sobre el Task Scheduler de Laravel:
- [Documentación oficial de Laravel](https://laravel.com/docs/scheduling)
- Ver código del comando: [`app/Console/Commands/CheckSlaCommand.php`](app/Console/Commands/CheckSlaCommand.php)
