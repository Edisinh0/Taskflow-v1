# 🚀 Comandos de Ejecución - Refactorización TaskFlow

## 📍 Ubicación Actual
Estás en: `/Users/eddiecerpa/Taskflow-Icontel`

---

## Paso 1: Verificar Contenedores Docker

```bash
# Ver contenedores corriendo
docker ps

# Deberías ver algo como:
# CONTAINER ID   IMAGE            NAMES
# xxxxxxxxx      taskflow-app     taskflow-app
# xxxxxxxxx      mariadb:10.6     taskflow-db
```

---

## Paso 2: Ejecutar Migración

### Opción A: Si usas docker-compose

```bash
docker-compose exec taskflow-app php artisan migrate
```

### Opción B: Si usas docker directamente

```bash
# Encuentra el nombre del contenedor
docker ps | grep taskflow

# Ejecuta la migración (reemplaza NOMBRE_CONTENEDOR)
docker exec -it NOMBRE_CONTENEDOR php artisan migrate
```

### Resultado Esperado:
```
Migrating: 2025_12_17_000001_add_role_to_users_table
Migrated:  2025_12_17_000001_add_role_to_users_table (XX.XXms)
```

---

## Paso 3: Actualizar Roles de Usuarios

```bash
# Entrar a tinker
docker-compose exec taskflow-app php artisan tinker

# O con docker directo:
docker exec -it NOMBRE_CONTENEDOR php artisan tinker
```

### Dentro de tinker, ejecutar:

```php
// Actualizar admin
User::where('email', 'admin@taskflow.com')->update(['role' => 'admin']);

// Actualizar PMs (ajusta el email según tu base de datos)
User::where('email', 'pm@taskflow.com')->update(['role' => 'project_manager']);

// Actualizar todos los demás como usuarios operativos
User::whereNotIn('role', ['admin', 'project_manager'])
    ->whereNull('role')
    ->update(['role' => 'user']);

// Verificar los cambios
User::select('id', 'name', 'email', 'role')->get();

// Salir
exit
```

---

## Paso 4: Verificar Rutas API

```bash
docker-compose exec taskflow-app php artisan route:list | grep -E "(flow-builder|task-center)"

# O con docker directo:
docker exec -it NOMBRE_CONTENEDOR php artisan route:list | grep -E "(flow-builder|task-center)"
```

### Resultado Esperado:
```
POST   api/v1/flow-builder/flows
PUT    api/v1/flow-builder/flows/{id}
DELETE api/v1/flow-builder/flows/{id}
POST   api/v1/flow-builder/tasks
PUT    api/v1/flow-builder/tasks/{id}
DELETE api/v1/flow-builder/tasks/{id}
PUT    api/v1/flow-builder/tasks/{id}/dependencies
GET    api/v1/task-center/my-tasks
GET    api/v1/task-center/tasks/{id}
PUT    api/v1/task-center/tasks/{id}/execute
```

---

## Paso 5: Limpiar Cache (Importante)

```bash
docker-compose exec taskflow-app php artisan config:clear
docker-compose exec taskflow-app php artisan cache:clear
docker-compose exec taskflow-app php artisan route:clear
docker-compose exec taskflow-app php artisan optimize
```

---

## Paso 6: Reiniciar Contenedores

```bash
docker-compose restart
```

---

## 🧪 Paso 7: Probar la Implementación

### Test 1: Login como Admin

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@taskflow.com",
    "password": "password"
  }'
```

Guarda el token que te devuelve.

### Test 2: Admin puede crear flujos

```bash
curl -X POST http://localhost:8000/api/v1/flow-builder/flows \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Flow Refactorizado",
    "description": "Prueba del nuevo módulo Flow Builder"
  }'
```

**Resultado esperado:** 201 Created ✅

### Test 3: Usuario operativo NO puede crear flujos

```bash
# Primero login como usuario normal
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@taskflow.com",
    "password": "password"
  }'

# Guardar el token de usuario

# Intentar crear flujo (debe fallar)
curl -X POST http://localhost:8000/api/v1/flow-builder/flows \
  -H "Authorization: Bearer TOKEN_DE_USUARIO" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Flow"
  }'
```

**Resultado esperado:** 403 Forbidden ❌ (Correcto, está protegido)

### Test 4: Usuario puede ver sus tareas

```bash
curl -X GET http://localhost:8000/api/v1/task-center/my-tasks \
  -H "Authorization: Bearer TOKEN_DE_USUARIO"
```

**Resultado esperado:** 200 OK con lista de tareas ✅

---

## 📊 Si algo falla

### Problema: "Class 'FlowPolicy' not found"

```bash
docker-compose exec taskflow-app composer dump-autoload
docker-compose exec taskflow-app php artisan config:clear
docker-compose exec taskflow-app php artisan optimize
```

### Problema: "Route [flow-builder.flows.store] not defined"

```bash
docker-compose exec taskflow-app php artisan route:clear
docker-compose exec taskflow-app php artisan route:cache
docker-compose restart
```

### Problema: Base de datos no se conecta

Verifica tu archivo `.env`:
```bash
cat taskflow-backend/.env | grep DB_
```

Debería mostrar:
```
DB_CONNECTION=mysql
DB_HOST=mariadb  # O el nombre de tu contenedor de BD
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=root
```

---

## 📚 Documentación de Referencia

- **MANUAL_EXECUTION_GUIDE.md** - Guía detallada paso a paso
- **REFACTOR_GUIDE.md** - Guía técnica completa
- **INTEGRATION_EXAMPLES.md** - Ejemplos de código

---

## ✅ Checklist Final

- [ ] Migración ejecutada correctamente
- [ ] Roles de usuarios actualizados
- [ ] Rutas verificadas con `route:list`
- [ ] Cache limpiado
- [ ] Contenedores reiniciados
- [ ] Test de admin crear flujo (201) ✅
- [ ] Test de user crear flujo (403) ❌
- [ ] Test de user ver tareas (200) ✅

---

**¡La implementación está completa! Solo necesitas ejecutar estos comandos.**
