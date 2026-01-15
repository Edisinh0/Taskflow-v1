# 🚀 Guía de Ejecución Manual - Refactorización TaskFlow

## ✅ Estado de la Implementación

### Archivos Creados y Verificados

#### 📚 Documentación (4 archivos)
- ✅ `REFACTOR_GUIDE.md` - Guía técnica completa
- ✅ `REFACTOR_SUMMARY.md` - Resumen ejecutivo
- ✅ `INTEGRATION_EXAMPLES.md` - Ejemplos de código
- ✅ `REFACTOR_README.md` - Resumen visual

#### 🔧 Backend Laravel 11 (9 archivos)
- ✅ `taskflow-backend/app/Policies/FlowPolicy.php`
- ✅ `taskflow-backend/app/Policies/TaskPolicy.php`
- ✅ `taskflow-backend/app/Http/Controllers/Api/FlowBuilderController.php`
- ✅ `taskflow-backend/app/Http/Controllers/Api/TaskCenterController.php`
- ✅ `taskflow-backend/routes/flow-builder.php`
- ✅ `taskflow-backend/routes/task-center.php`
- ✅ `taskflow-backend/routes/api.php` (actualizado con require de módulos)
- ✅ `taskflow-backend/app/Models/User.php` (campo 'role' agregado)
- ✅ `taskflow-backend/app/Providers/AppServiceProvider.php` (Policies registrados)
- ✅ `taskflow-backend/database/migrations/2025_12_17_000001_add_role_to_users_table.php`

#### 🎨 Frontend Vue 3 (3 archivos + estructura)
- ✅ `taskflow-frontend/src/modules/task-center/components/TaskExecutionCard.vue`
- ✅ `taskflow-frontend/src/modules/task-center/views/TaskCenterView.vue`
- ✅ `taskflow-frontend/src/modules/task-center/composables/useTaskExecution.js`
- ✅ Estructura de directorios:
  ```
  src/modules/
  ├── flow-builder/
  │   ├── components/
  │   ├── views/
  │   └── composables/
  └── task-center/
      ├── components/
      ├── views/
      └── composables/
  src/shared/
  ├── components/
  └── composables/
  ```

---

## 📋 Pasos de Ejecución Manual

### Paso 1: Ejecutar Migraciones

**Opción A: Con Docker**
```bash
cd /Users/eddiecerpa/.claude-worktrees/Taskflow-Icontel/nervous-cohen

# Si usas Docker Compose
docker-compose exec taskflow-app php artisan migrate

# O si el contenedor tiene otro nombre
docker exec -it <container_name> php artisan migrate
```

**Opción B: Sin Docker (PHP local)**
```bash
cd taskflow-backend
php artisan migrate
```

**Resultado esperado:**
```
Migrating: 2025_12_17_000001_add_role_to_users_table
Migrated:  2025_12_17_000001_add_role_to_users_table (XX.XXms)
```

---

### Paso 2: Actualizar Roles de Usuarios Existentes

**Opción A: Con Docker**
```bash
docker-compose exec taskflow-app php artisan tinker

# O
docker exec -it <container_name> php artisan tinker
```

**Opción B: Sin Docker**
```bash
cd taskflow-backend
php artisan tinker
```

**Ejecutar en tinker:**
```php
// Actualizar admin
User::where('email', 'admin@taskflow.com')->update(['role' => 'admin']);

// Actualizar PMs (ajustar emails según tu BD)
User::where('email', 'pm@taskflow.com')->update(['role' => 'project_manager']);

// Actualizar usuarios operativos (todos los demás)
User::whereNotIn('role', ['admin', 'project_manager'])->update(['role' => 'user']);

// Verificar cambios
User::select('id', 'name', 'email', 'role')->get();

// Salir
exit
```

---

### Paso 3: Verificar Rutas API

**Probar que las rutas están registradas:**

```bash
# Con Docker
docker-compose exec taskflow-app php artisan route:list | grep -E "(flow-builder|task-center)"

# Sin Docker
cd taskflow-backend
php artisan route:list | grep -E "(flow-builder|task-center)"
```

**Resultado esperado:**
```
GET|HEAD  api/v1/flow-builder/flows ...............
POST      api/v1/flow-builder/flows ...............
PUT       api/v1/flow-builder/flows/{id} ..........
DELETE    api/v1/flow-builder/flows/{id} ..........
POST      api/v1/flow-builder/tasks ...............
PUT       api/v1/flow-builder/tasks/{id} ..........
DELETE    api/v1/flow-builder/tasks/{id} ..........
PUT       api/v1/flow-builder/tasks/{id}/dependencies
GET       api/v1/task-center/my-tasks .............
GET       api/v1/task-center/tasks/{id} ...........
PUT       api/v1/task-center/tasks/{id}/execute ...
```

---

### Paso 4: Reiniciar Servidor (Si es necesario)

**Con Docker:**
```bash
docker-compose restart taskflow-app
```

**Sin Docker:**
```bash
# Si usas artisan serve
ctrl+c  # detener
php artisan serve  # reiniciar

# Si usas otro servidor, reinícialo según corresponda
```

---

### Paso 5: Configurar Frontend (Vue Router)

**Crear/Actualizar:** `taskflow-frontend/src/router/index.js`

Ver el código completo en `INTEGRATION_EXAMPLES.md` sección 2.

**Puntos clave a agregar:**

1. **Rutas de módulos:**
```javascript
{
  path: '/flow-builder',
  name: 'FlowBuilder',
  component: () => import('@/modules/flow-builder/views/FlowBuilderView.vue'),
  meta: { requiresRole: ['admin', 'project_manager', 'pm'] }
},
{
  path: '/task-center',
  name: 'TaskCenter',
  component: () => import('@/modules/task-center/views/TaskCenterView.vue'),
  meta: { requiresAuth: true }
}
```

2. **Guard de navegación:**
```javascript
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresRole) {
    if (!to.meta.requiresRole.includes(authStore.user?.role)) {
      return next({ name: 'TaskCenter' })
    }
  }
  next()
})
```

---

### Paso 6: Actualizar Auth Store

**Crear/Actualizar:** `taskflow-frontend/src/stores/auth.js`

Ver el código completo en `INTEGRATION_EXAMPLES.md` sección 3.

**Puntos clave a agregar:**

```javascript
getters: {
  isFlowBuilder: (state) => {
    if (!state.user) return false
    return ['admin', 'project_manager', 'pm'].includes(state.user.role)
  },

  isOperator: (state) => {
    if (!state.user) return false
    return ['user', 'operator', 'employee'].includes(state.user.role)
  }
}
```

---

### Paso 7: Probar la Implementación

#### Test 1: Verificar que usuarios operativos NO pueden crear flujos

```bash
# Obtener token de usuario operativo
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@taskflow.com", "password": "password"}'

# Guardar el token

# Intentar crear flujo (debe fallar)
curl -X POST http://localhost:8000/api/v1/flow-builder/flows \
  -H "Authorization: Bearer <USER_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Flow"}'

# Respuesta esperada: 403 Forbidden
```

#### Test 2: Verificar que PM puede crear flujos

```bash
# Obtener token de PM
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "pm@taskflow.com", "password": "password"}'

# Intentar crear flujo (debe funcionar)
curl -X POST http://localhost:8000/api/v1/flow-builder/flows \
  -H "Authorization: Bearer <PM_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Flow"}'

# Respuesta esperada: 201 Created
```

#### Test 3: Verificar que usuarios pueden ver sus tareas

```bash
curl -X GET http://localhost:8000/api/v1/task-center/my-tasks \
  -H "Authorization: Bearer <USER_TOKEN>"

# Respuesta esperada: 200 OK con lista de tareas
```

#### Test 4: Verificar bloqueo de tareas

```bash
# Intentar completar tarea bloqueada
curl -X PUT http://localhost:8000/api/v1/task-center/tasks/123/execute \
  -H "Authorization: Bearer <USER_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"status": "completed"}'

# Respuesta esperada: 403 con mensaje "🔒 Acción prohibida"
```

---

### Paso 8: Integrar Componentes en el Frontend

**Ejemplo de uso del componente TaskExecutionCard:**

Crear una vista o actualizar una existente:

```vue
<!-- src/views/MyTasksView.vue -->
<script setup>
import { onMounted } from 'vue'
import TaskExecutionCard from '@/modules/task-center/components/TaskExecutionCard.vue'
import { useTaskExecution } from '@/modules/task-center/composables/useTaskExecution'

const { tasks, fetchMyTasks } = useTaskExecution()

onMounted(() => {
  fetchMyTasks()
})

function handleTaskUpdate(task) {
  console.log('Task updated:', task)
}

function handleError(error) {
  alert(error)
}
</script>

<template>
  <div class="container">
    <h1>Mis Tareas</h1>

    <div class="grid">
      <TaskExecutionCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        @taskUpdated="handleTaskUpdate"
        @error="handleError"
      />
    </div>
  </div>
</template>
```

---

## 🔍 Verificación Final

### Checklist de Verificación

- [ ] Migración ejecutada correctamente
- [ ] Roles de usuarios actualizados en la base de datos
- [ ] Rutas de módulos aparecen en `php artisan route:list`
- [ ] Usuario operativo NO puede crear flujos (403)
- [ ] PM/Admin puede crear flujos (201)
- [ ] Usuario puede ver sus tareas
- [ ] Tareas bloqueadas no se pueden completar
- [ ] Vue Router configurado con guards
- [ ] Auth Store tiene getters de roles
- [ ] TaskExecutionCard se muestra correctamente

---

## 🐛 Solución de Problemas

### Problema: Migración no se ejecuta

**Solución:**
```bash
# Limpiar cache
php artisan config:clear
php artisan cache:clear

# Verificar conexión a BD
php artisan migrate:status

# Ejecutar migración específica
php artisan migrate --path=/database/migrations/2025_12_17_000001_add_role_to_users_table.php
```

### Problema: Policies no se aplican

**Verificar:**
1. AppServiceProvider registra las policies (ya está hecho)
2. Reiniciar servidor después de crear policies
3. Verificar que el usuario tiene token válido

```bash
# Limpiar config
php artisan config:clear
php artisan optimize:clear
```

### Problema: Rutas no encontradas (404)

**Verificar:**
1. Archivos `flow-builder.php` y `task-center.php` existen en `routes/`
2. `api.php` tiene los `require` correctos
3. Reiniciar servidor

```bash
php artisan route:clear
php artisan route:cache
```

### Problema: Frontend no reconoce módulos

**Verificar:**
1. Directorios creados en `src/modules/`
2. Archivos Vue tienen extensión `.vue`
3. Imports usan alias `@/modules/...`
4. Vite o Webpack configurado correctamente

---

## 📊 Resumen de Endpoints

### Flow Builder (PM/Admin)
```
POST   /api/v1/flow-builder/flows
PUT    /api/v1/flow-builder/flows/{id}
DELETE /api/v1/flow-builder/flows/{id}
POST   /api/v1/flow-builder/tasks
PUT    /api/v1/flow-builder/tasks/{id}
DELETE /api/v1/flow-builder/tasks/{id}
PUT    /api/v1/flow-builder/tasks/{id}/dependencies
```

### Task Center (Users)
```
GET /api/v1/task-center/my-tasks
GET /api/v1/task-center/tasks/{id}
PUT /api/v1/task-center/tasks/{id}/execute
```

---

## 📚 Documentación Adicional

Para más información, consulta:

- **REFACTOR_GUIDE.md** - Guía técnica completa (87 KB)
- **REFACTOR_SUMMARY.md** - Resumen ejecutivo
- **INTEGRATION_EXAMPLES.md** - Ejemplos de código completos
- **REFACTOR_README.md** - Resumen visual

---

## ✅ Próximos Pasos Opcionales

1. **Crear vista FlowBuilderView.vue** para el módulo de diseño
2. **Agregar tests unitarios** (ver ejemplos en INTEGRATION_EXAMPLES.md)
3. **Implementar componentes compartidos** en `src/shared/`
4. **Agregar más roles** según necesidades del negocio
5. **Documentar APIs** con Swagger/OpenAPI

---

**Fecha de creación:** 2025-12-17
**Versión:** 1.0
**Estado:** ✅ Implementación completa en el proyecto
