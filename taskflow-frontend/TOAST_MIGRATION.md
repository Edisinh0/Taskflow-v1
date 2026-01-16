# 🎉 Migración de alert() a Sistema de Toast

## Resumen

Se han reemplazado **todos los `alert()` nativos de JavaScript** por un sistema de notificaciones toast elegante usando **SweetAlert2**.

---

## ✨ Nuevo Composable: `useToast`

### Ubicación
[`src/composables/useToast.js`](src/composables/useToast.js)

### Funciones Disponibles

```javascript
import { useToast } from '@/composables/useToast'

const { showSuccess, showError, showWarning, showInfo, showConfirm, showModal } = useToast()
```

#### 1. **showSuccess(message, timer = 3000)**
Muestra un mensaje de éxito con icono verde.

```javascript
showSuccess('Plantilla creada exitosamente')
```

#### 2. **showError(message, timer = 4000)**
Muestra un mensaje de error con icono rojo.

```javascript
showError('Error al cargar el flujo')
```

#### 3. **showWarning(message, timer = 4000)**
Muestra un mensaje de advertencia con icono amarillo.

```javascript
showWarning('Esta tarea no te está asignada.')
```

#### 4. **showInfo(message, timer = 3000)**
Muestra un mensaje informativo con icono azul.

```javascript
showInfo('Recuerda guardar tus cambios')
```

#### 5. **showConfirm(title, text, confirmButtonText, cancelButtonText)**
Muestra un diálogo de confirmación. Retorna `true` si el usuario confirma, `false` si cancela.

```javascript
const confirmed = await showConfirm(
  '¿Eliminar tarea?',
  'Esta acción no se puede deshacer',
  'Sí, eliminar',
  'Cancelar'
)

if (confirmed) {
  // Usuario confirmó
}
```

#### 6. **showModal(title, text, icon)**
Muestra un modal centrado con más espacio para mensajes largos.

```javascript
showModal(
  'Acción no permitida',
  'Solo los administradores pueden realizar esta acción.',
  'warning'
)
```

---

## 📝 Archivos Modificados

### 1. **Composable Creado**
- ✅ [`src/composables/useToast.js`](src/composables/useToast.js) - Nuevo composable

### 2. **Componentes Actualizados**

| Archivo | Cambios | `alert()` Reemplazados |
|---------|---------|----------------------|
| [`src/components/FlowDiagram.vue`](src/components/FlowDiagram.vue) | showWarning | 1 |
| [`src/components/TaskAttachments.vue`](src/components/TaskAttachments.vue) | showError | 1 |

### 3. **Vistas Actualizadas**

| Archivo | Cambios | `alert()` Reemplazados |
|---------|---------|----------------------|
| [`src/views/FlowDetailView.vue`](src/views/FlowDetailView.vue) | showSuccess, showError, showWarning | 8 |
| [`src/views/FlowsView.vue`](src/views/FlowsView.vue) | showError | 1 |
| [`src/views/TemplatesView.vue`](src/views/TemplatesView.vue) | showError | 1 |
| [`src/views/ReportsView.vue`](src/views/ReportsView.vue) | showError | 2 |

**Total de `alert()` reemplazados: 14**

---

## 🎯 Ejemplos de Uso

### Antes y Después

#### ❌ Antes (alert nativo)
```javascript
alert('⚠️ Acción no permitida\n\nSolo los administradores pueden editar tareas.')
```

#### ✅ Después (useToast)
```javascript
import { useToast } from '@/composables/useToast'

const { showWarning } = useToast()

showWarning('Solo los administradores pueden editar tareas.')
```

---

### Ejemplo Completo en un Componente

```vue
<script setup>
import { useToast } from '@/composables/useToast'
import { tasksAPI } from '@/services/api'

const { showSuccess, showError, showConfirm } = useToast()

const deleteTask = async (taskId) => {
  // Confirmación
  const confirmed = await showConfirm(
    '¿Eliminar tarea?',
    'Esta acción no se puede deshacer',
    'Sí, eliminar',
    'Cancelar'
  )

  if (!confirmed) return

  try {
    await tasksAPI.delete(taskId)
    showSuccess('Tarea eliminada correctamente')
  } catch (error) {
    showError('Error al eliminar la tarea')
  }
}
</script>
```

---

## 🎨 Características del Sistema Toast

### Ventajas sobre `alert()` nativo

1. **✨ Diseño Moderno**: Notificaciones elegantes que no interrumpen
2. **⏱️ Auto-cierre**: Se ocultan automáticamente después de X segundos
3. **🎯 Posición Configurable**: Aparecen en la esquina superior derecha (no bloquean la vista)
4. **🎭 Iconos Visuales**: Success ✓, Error ✗, Warning ⚠, Info ℹ
5. **⏸️ Pausa al Hover**: Se pausa el timer cuando el mouse está sobre la notificación
6. **📱 Responsive**: Se adapta a diferentes tamaños de pantalla
7. **🌗 Compatible con Modo Oscuro**: Se integra con el tema del proyecto

### Configuración Actual

```javascript
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',           // Esquina superior derecha
  showConfirmButton: false,      // Sin botón de confirmación
  timer: 3000,                   // 3 segundos por defecto
  timerProgressBar: true,        // Barra de progreso visible
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)   // Pausa al hover
    toast.addEventListener('mouseleave', Swal.resumeTimer) // Resume al salir
  }
})
```

---

## 🔄 Migración de Código Existente

Si necesitas agregar más notificaciones en el futuro:

### 1. Importar el composable
```javascript
import { useToast } from '@/composables/useToast'
```

### 2. Destructurar las funciones necesarias
```javascript
const { showSuccess, showError, showWarning } = useToast()
```

### 3. Usar en lugar de alert()
```javascript
// ❌ No hacer
alert('Error al guardar')

// ✅ Hacer
showError('Error al guardar')
```

---

## 📚 Documentación de SweetAlert2

Para personalizaciones avanzadas:
- [Documentación oficial](https://sweetalert2.github.io/)
- [Ejemplos interactivos](https://sweetalert2.github.io/#examples)

---

## 🧪 Testing

Las notificaciones toast funcionan correctamente en:
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Modo claro y oscuro
- ✅ Dispositivos móviles
- ✅ Diferentes resoluciones de pantalla

---

## 🚀 Próximos Pasos

Considera reemplazar también los `confirm()` nativos:

```javascript
// ❌ Antes
if (!confirm('¿Estás seguro?')) return

// ✅ Después
const confirmed = await showConfirm('¿Estás seguro?', 'Esta acción no se puede deshacer')
if (!confirmed) return
```

---

## 📞 Soporte

Si encuentras algún problema o necesitas agregar más funcionalidades al sistema de toast:
1. Modifica [`src/composables/useToast.js`](src/composables/useToast.js)
2. Consulta la [documentación de SweetAlert2](https://sweetalert2.github.io/)
3. Mantén la consistencia en el uso de las funciones

---

**Actualizado:** 2026-01-15
**Versión:** 1.0.0
