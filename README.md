# 📋 Taskflow - Sistema de Gestión de Flujos de Trabajo

<div align="center">

![Taskflow Banner](https://img.shields.io/badge/Taskflow-Sistema_de_Gestión-blue?style=for-the-badge)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)

**Sistema completo de gestión de flujos de trabajo y tareas para empresas**

[Características](#-características-principales) • [Instalación](#-instalación) • [Uso](#-uso) • [Tecnologías](#-tecnologías) • [Contribuir](#-contribuir)

</div>

---

## 📖 Descripción

**Taskflow** es una aplicación web moderna diseñada para optimizar y automatizar el flujo de actividades y tareas empresariales. Desarrollada principalmente para **TNA Group**, esta plataforma permite gestionar proyectos, asignar tareas, establecer dependencias y monitorear el progreso en tiempo real.

### 🎯 Propósito

Facilitar la gestión de proyectos complejos mediante:
- Visualización clara de flujos de trabajo
- Control de dependencias entre tareas
- Notificaciones automáticas
- Seguimiento de progreso en tiempo real
- Sistema de bloqueo de tareas basado en dependencias

---

## ✨ Características Principales

### 🔄 Gestión de Flujos
- **Creación de flujos** personalizados desde plantillas o desde cero
- **Visualización jerárquica** de tareas con árbol expandible
- **Progreso automático** calculado en base a tareas completadas
- **Estados de flujo**: Activo, Pausado, Completado

### 📝 Gestión de Tareas
- **Creación y edición** de tareas con información detallada
- **Subtareas ilimitadas** con estructura jerárquica
- **Prioridades**: Baja, Media, Alta, Urgente
- **Estados**: Pendiente, En Progreso, Completado, Pausado, Cancelado
- **Asignación** de responsables
- **Fechas estimadas** de inicio y fin

### 🔗 Sistema de Dependencias
- **Tareas precedentes**: Bloqueo hasta completar tarea anterior
- **Milestones**: Hitos que desbloquean múltiples tareas
- **Bloqueo automático**: Tareas se bloquean si sus dependencias no están completadas
- **Desbloqueo inteligente**: Liberación automática al completar dependencias
- **Prevención de ciclos**: Evita dependencias circulares

### 🔔 Sistema de Notificaciones
- **Notificaciones automáticas** para eventos clave:
  - Tarea bloqueada/desbloqueada
  - Tarea asignada
  - Tarea completada
  - Milestone completado
- **Centro de notificaciones** con filtros
- **Badge en tiempo real** con contador de no leídas
- **Navegación directa** desde notificación a tarea

### 📊 Dashboard Analítico
- **Estadísticas en tiempo real**:
  - Flujos activos
  - Tareas pendientes
  - Tareas completadas hoy
  - Tareas vencidas
- **Gráficos interactivos**:
  - Tendencia de tareas (últimos 7 días)
  - Distribución por prioridad
- **Resumen de productividad** semanal
- **Lista de tareas urgentes**
- **Flujos recientes** con progreso

### 🎨 Interfaz Moderna
- **Modo oscuro** completo
- **Diseño responsivo** para todos los dispositivos
- **Animaciones suaves** y transiciones
- **Gráficos con efectos hover** y gradientes
- **Tooltips informativos**

### 🔐 Seguridad
- **Autenticación JWT** con tokens seguros
- **Protección de rutas** con middleware
- **Validación de datos** en frontend y backend
- **CORS configurado** para seguridad API

---

## 🛠️ Tecnologías

### Backend
- **Laravel 11.x** - Framework PHP
- **MySQL** - Base de datos relacional
- **JWT Auth** - Autenticación con tokens
- **Laravel Sanctum** - API authentication
- **Eloquent ORM** - Manejo de base de datos
- **Laravel Observers** - Lógica de eventos automáticos

### Frontend
- **Vue.js 3** - Framework JavaScript progresivo
- **Vue Router** - Navegación SPA
- **Pinia** - State management
- **Axios** - Cliente HTTP
- **Chart.js** - Gráficos interactivos
- **Tailwind CSS** - Framework de estilos
- **Vite** - Build tool

### DevOps
- **Docker** - Contenedorización
- **Docker Compose** - Orquestación de servicios
- **Git** - Control de versiones

---

## 📦 Instalación

> 💡 **Para desarrolladores**: Consulta [DESARROLLO.md](DESARROLLO.md) para configurar un entorno de desarrollo con Hot Module Replacement.

### Prerequisitos

- Docker & Docker Compose
- Git
- Node.js 18+ (para desarrollo frontend)
- Composer (para desarrollo backend)

### 1. Clonar el Repositorio

```bash
git clone https://github.com/Edisinh0/Taskflow-Icontel.git
cd Taskflow-Icontel
```

### 2. Configurar Backend

```bash
cd taskflow-backend

# Copiar archivo de configuración
cp .env.example .env

# Editar .env con tus credenciales de base de datos
# DB_HOST=mysql
# DB_DATABASE=taskflow
# DB_USERNAME=root
# DB_PASSWORD=root

# Instalar dependencias
composer install

# Generar key de aplicación
php artisan key:generate

# Generar secret JWT
php artisan jwt:secret
```

### 3. Configurar Frontend

```bash
cd ../taskflow-frontend

# Instalar dependencias
npm install

# Copiar archivo de configuración (si existe)
cp .env.example .env
```

### 4. Levantar con Docker

```bash
# Desde el directorio raíz del proyecto
docker-compose up -d

# Ejecutar migraciones
docker exec -it taskflow-app php artisan migrate

# Ejecutar seeders (opcional - datos de prueba)
docker exec -it taskflow-app php artisan db:seed
```

### 5. Acceder a la Aplicación

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api/v1
- **Base de datos**: localhost:3306

### Credenciales por Defecto

```
Email: admin@taskflow.com
Password: password
```

---

## 🚀 Uso

### Crear un Flujo

1. Navega a **Flujos** en el menú
2. Click en **"Nuevo Flujo"**
3. Selecciona una plantilla o crea desde cero
4. Completa nombre, descripción y fechas
5. Agrega tareas al flujo

### Gestionar Tareas

1. Abre un flujo existente
2. Click en **"Nueva Tarea"** o edita una existente
3. Configura:
   - Título y descripción
   - Prioridad y estado
   - Asignado
   - **Dependencias** (tarea precedente o milestone)
   - Fechas estimadas
4. Guarda los cambios

### Configurar Dependencias

**Opción 1: Al crear/editar tarea**
- En el modal de tarea, sección "Dependencias"
- Selecciona tarea precedente o milestone requerido

**Opción 2: Gestor de dependencias**
- Click en el icono 🔗 de la tarea
- Selecciona dependencias en el modal

### Ver Notificaciones

- Click en el icono 🔔 en la barra superior
- Ver notificaciones recientes en el dropdown
- Click en "Ver todas" para página completa
- Filtrar por leídas/no leídas

### Monitorear Progreso

- **Dashboard**: Vista general de estadísticas
- **Gráficos**: Tendencias y distribución
- **Flujos**: Barra de progreso en cada flujo
- **Tareas**: Indicador de progreso automático

---

## 📁 Estructura del Proyecto

```
Taskflow-Icontel/
├── taskflow-backend/          # Backend Laravel
│   ├── app/
│   │   ├── Http/Controllers/  # Controladores API
│   │   ├── Models/            # Modelos Eloquent
│   │   ├── Observers/         # Observers (lógica automática)
│   │   └── Services/          # Servicios (NotificationService)
│   ├── database/
│   │   ├── migrations/        # Migraciones de BD
│   │   └── seeders/           # Seeders de datos
│   └── routes/
│       └── api.php            # Rutas API
│
├── taskflow-frontend/         # Frontend Vue.js
│   ├── src/
│   │   ├── components/        # Componentes Vue
│   │   │   ├── DependencyManager.vue
│   │   │   ├── NotificationBell.vue
│   │   │   ├── TaskModal.vue
│   │   │   └── TaskTreeItem.vue
│   │   ├── views/             # Vistas/Páginas
│   │   │   ├── DashboardView.vue
│   │   │   ├── FlowsView.vue
│   │   │   ├── FlowDetailView.vue
│   │   │   └── NotificationsView.vue
│   │   ├── stores/            # Pinia stores
│   │   ├── services/          # API services
│   │   └── router/            # Vue Router
│   └── public/
│
└── docker-compose.yml         # Configuración Docker
```

---

## 🔧 API Endpoints

### Autenticación
```
POST   /api/v1/login          # Login
POST   /api/v1/logout         # Logout
GET    /api/v1/me             # Usuario actual
```

### Flujos
```
GET    /api/v1/flows          # Listar flujos
POST   /api/v1/flows          # Crear flujo
GET    /api/v1/flows/{id}     # Ver flujo
PUT    /api/v1/flows/{id}     # Actualizar flujo
DELETE /api/v1/flows/{id}     # Eliminar flujo
```

### Tareas
```
GET    /api/v1/tasks          # Listar tareas
POST   /api/v1/tasks          # Crear tarea
GET    /api/v1/tasks/{id}     # Ver tarea
PUT    /api/v1/tasks/{id}     # Actualizar tarea
DELETE /api/v1/tasks/{id}     # Eliminar tarea
```

### Notificaciones
```
GET    /api/v1/notifications              # Listar notificaciones
PUT    /api/v1/notifications/{id}/read    # Marcar como leída
POST   /api/v1/notifications/read-all     # Marcar todas como leídas
DELETE /api/v1/notifications/{id}         # Eliminar notificación
GET    /api/v1/notifications/stats        # Estadísticas
```

---

## 🎨 Capturas de Pantalla

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)
*Vista general con estadísticas y gráficos interactivos*

### Gestión de Flujos
![Flujos](docs/screenshots/flows.png)
*Lista de flujos con progreso y estados*

### Detalle de Flujo
![Detalle](docs/screenshots/flow-detail.png)
*Vista de tareas en árbol jerárquico con dependencias*

### Notificaciones
![Notificaciones](docs/screenshots/notifications.png)
*Centro de notificaciones con filtros*

---

## 🧪 Testing

### Backend
```bash
cd taskflow-backend
php artisan test
```

### Frontend
```bash
cd taskflow-frontend
npm run test
```

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📝 Changelog

### v1.0.0 (2025-12-08)
- ✅ Sistema completo de gestión de flujos y tareas
- ✅ Dependencias y bloqueo automático de tareas
- ✅ Sistema de notificaciones en tiempo real
- ✅ Dashboard con gráficos interactivos
- ✅ Modo oscuro completo
- ✅ Autenticación JWT
- ✅ API RESTful completa

---

## 👥 Autores

- **Eddie Cerpa** - *Desarrollo y mantenimiento*

---

## 🙏 Agradecimientos

- Laravel Framework
- Vue.js Team
- Chart.js Contributors
- Tailwind CSS Team
- Comunidad Open Source

---

## 📞 Contacto

Para preguntas o soporte:
- **Email**: ed.cerpa@duocuc.cl
- **GitHub Issues**: [Crear Issue](https://github.com/Edisinh0/Taskflow-Icontel/issues)

---

<div align="center">

**Hecho con ❤️ por Edisinh0**

[⬆ Volver arriba](#-taskflow---sistema-de-gestión-de-flujos-de-trabajo)

</div>
