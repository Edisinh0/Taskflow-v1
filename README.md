# 📋 Taskflow - Sistema de Gestión de Flujos de Trabajo

<div align="center">

![Taskflow Banner](https://img.shields.io/badge/Taskflow-Sistema_de_Gestión-blue?style=for-the-badge)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Node.js](https://img.shields.io/badge/Node.js-20.x-339933?style=for-the-badge&logo=node.js&logoColor=white)](https://nodejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)

**Sistema completo de gestión de flujos de trabajo y tareas para empresas**

[Características](#-características-principales) • [Instalación](#-instalación) • [Uso](#-uso) • [Tecnologías](#-tecnologías) • [Documentación](#-documentación)

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
- **Laravel 12.x** - Framework PHP moderno
- **PHP 8.2+** - Lenguaje de programación
- **MySQL** - Base de datos relacional
- **Laravel Sanctum 4.x** - Autenticación API
- **Eloquent ORM** - Manejo de base de datos
- **Laravel Observers** - Lógica de eventos automáticos
- **Laravel Auditing** - Registro de cambios
- **DomPDF** - Generación de PDFs

### Frontend
- **Vue.js 3.5** - Framework JavaScript progresivo
- **Vue Router 4.x** - Navegación SPA
- **Pinia 3.x** - State management moderno
- **Axios 1.13** - Cliente HTTP
- **Chart.js 4.5** - Gráficos interactivos
- **Vue Flow** - Diagramas de flujo interactivos
- **Tailwind CSS 3.4** - Framework de estilos
- **Vite 7.x** - Build tool rápido y moderno
- **SweetAlert2** - Alertas y modales elegantes
- **Lucide Icons** - Iconografía moderna
- **HeadlessUI** - Componentes accesibles
- **Socket.io** - Comunicación en tiempo real
- **Pusher/Laravel Echo** - Broadcasting de eventos

### DevOps & Herramientas
- **Docker** - Contenedorización
- **Docker Compose** - Orquestación de servicios
- **GitHub Actions** - CI/CD automatizado
- **ESLint & Prettier** - Linting y formateo de código
- **Laravel Pint** - Code style fixer para PHP
- **Git** - Control de versiones

---

## 📦 Instalación

### 🚀 Inicio Rápido

Para un inicio rápido, usa el script de desarrollo incluido:

```bash
# Clonar el repositorio
git clone https://github.com/tu-usuario/taskflow.git
cd taskflow

# Ejecutar entorno de desarrollo
./dev.sh
```

Luego abre **http://localhost:5173** en tu navegador.

> 💡 **Nota**: Los cambios en Vue se reflejan automáticamente con Hot Module Replacement (HMR).

---

### 📋 Prerequisitos

- **Docker & Docker Compose** (para producción)
- **Git** - Control de versiones
- **Node.js 20.x o superior** - Para desarrollo frontend
- **PHP 8.2+** - Para desarrollo backend
- **Composer** - Gestor de dependencias PHP
- **MySQL 8.0+** - Base de datos (o usar Docker)

---

### 🔧 Instalación Completa (Desarrollo)

#### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/taskflow.git
cd taskflow
```

#### 2. Configurar Backend

```bash
cd taskflow-backend

# Copiar archivo de configuración
cp .env.example .env

# Editar .env con tus credenciales
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=taskflow
# DB_USERNAME=root
# DB_PASSWORD=

# Instalar dependencias
composer install

# Generar key de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (opcional - datos de prueba)
php artisan db:seed
```

#### 3. Configurar Frontend

```bash
cd ../taskflow-frontend

# Instalar dependencias
npm install
```

#### 4. Iniciar Desarrollo

**Opción A: Con script de desarrollo (recomendado)**
```bash
# Desde el directorio raíz
./dev.sh
```

**Opción B: Manual**
```bash
# Terminal 1 - Backend
cd taskflow-backend
php artisan serve

# Terminal 2 - Frontend
cd taskflow-frontend
npm run dev
```

---

### 🐳 Instalación con Docker (Producción)

#### 1. Configurar Variables de Entorno

```bash
cd taskflow-backend

# Copiar y configurar .env
cp .env.example .env.docker
# Editar .env.docker con configuración de producción
```

#### 2. Construir y Levantar Contenedores

```bash
docker-compose up -d
```

#### 3. Ejecutar Migraciones

```bash
docker-compose exec backend php artisan migrate --force

# Seeders opcionales
docker-compose exec backend php artisan db:seed --force
```

#### 4. Acceder a la Aplicación

- **Aplicación**: http://localhost
- **Backend API**: http://localhost:8000
- **Base de datos**: localhost:3306

---

### 🔑 Credenciales por Defecto

```
Email: admin@taskflow.com
Password: password
```

> ⚠️ **Importante**: Cambia estas credenciales en producción.

---

### 📚 Más Información

- **[QUICK_START.md](QUICK_START.md)** - Guía de inicio rápido
- **[DESARROLLO.md](DESARROLLO.md)** - Configuración de desarrollo completa
- **[PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)** - Guía de despliegue a producción
- **[SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)** - Instrucciones detalladas de configuración

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

# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=TaskTest

# Con coverage
php artisan test --coverage
```

### Frontend
```bash
cd taskflow-frontend

# Lint del código
npm run lint

# Formatear código
npm run format
```

---

## 📊 CI/CD

El proyecto incluye GitHub Actions para CI/CD automatizado:

- **Frontend CI**: Ejecuta lint y build en cada push/PR
- **Triggers**: Push a `main`/`master` o cambios en `taskflow-frontend/`
- **Node.js**: v20.x

Ver configuración en [`.github/workflows/frontend.yml`](.github/workflows/frontend.yml)

---

## 📁 Comandos Útiles

### Desarrollo

```bash
# Modo desarrollo completo (backend + frontend + queue + logs)
cd taskflow-backend
composer run dev

# Solo backend
php artisan serve

# Solo frontend
cd taskflow-frontend
npm run dev

# Ver logs en tiempo real
php artisan pail

# Procesar colas
php artisan queue:listen
```

### Docker

```bash
# Levantar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f backend
docker-compose logs -f frontend

# Reiniciar servicio
docker-compose restart backend

# Detener todo
docker-compose down

# Reconstruir imagen
docker-compose build frontend
```

### Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Refrescar BD (elimina y recrea)
php artisan migrate:fresh

# Seeders
php artisan db:seed

# Crear migración
php artisan make:migration create_table_name
```

---

## 📚 Documentación

El proyecto incluye documentación completa:

| Archivo | Descripción |
|---------|-------------|
| [QUICK_START.md](QUICK_START.md) | Guía de inicio rápido |
| [DESARROLLO.md](DESARROLLO.md) | Configuración de entorno de desarrollo |
| [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md) | Despliegue a producción |
| [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) | Instrucciones de configuración |
| [TASKFLOW_ARCHITECTURE.md](TASKFLOW_ARCHITECTURE.md) | Arquitectura del sistema |
| [TASKFLOW_COMPONENTS_INTERACTION.md](TASKFLOW_COMPONENTS_INTERACTION.md) | Interacción de componentes |
| [COMANDOS_EJECUCION.md](COMANDOS_EJECUCION.md) | Comandos de ejecución |
| [CI_CD_GUIDE.md](CI_CD_GUIDE.md) | Guía de CI/CD |
| [DEPLOY_QUICK_GUIDE.md](DEPLOY_QUICK_GUIDE.md) | Guía rápida de despliegue |

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/NuevaCaracteristica`)
3. Realiza tus cambios y asegúrate de que pasen los tests
4. Commit tus cambios (`git commit -m 'feat: Agregar nueva característica'`)
5. Push a la rama (`git push origin feature/NuevaCaracteristica`)
6. Abre un Pull Request

### Guías de Estilo

- **Backend**: Sigue las convenciones de Laravel y PSR-12
- **Frontend**: Usa ESLint y Prettier (configurados en el proyecto)
- **Commits**: Usa [Conventional Commits](https://www.conventionalcommits.org/)

---

## 📝 Changelog

### v1.1.0 (2026-01-15)
- ✨ Actualización a Laravel 12.x
- ✨ Actualización a Vue.js 3.5
- ✨ Integración de Vue Flow para diagramas
- ✨ Mejoras en sistema de notificaciones en tiempo real
- ✨ GitHub Actions para CI/CD
- 🔧 Script de desarrollo mejorado
- 📚 Documentación actualizada y expandida

### v1.0.0 (2025-12-08)
- ✅ Sistema completo de gestión de flujos y tareas
- ✅ Dependencias y bloqueo automático de tareas
- ✅ Sistema de notificaciones en tiempo real
- ✅ Dashboard con gráficos interactivos
- ✅ Modo oscuro completo
- ✅ Autenticación con Laravel Sanctum
- ✅ API RESTful completa

---

## 👥 Equipo

### Desarrolladores
- **Eddie Cerpa** - *Desarrollo principal y mantenimiento*
  - GitHub: [@Edisinh0](https://github.com/Edisinh0)
  - Email: ed.cerpa@duocuc.cl

### Organización
- **TNA Group** - Cliente principal

---

## 📄 Licencia

Este proyecto es de código cerrado y está desarrollado para uso interno de TNA Group.

---

## 🙏 Agradecimientos

- [Laravel Framework](https://laravel.com) - Framework PHP elegante
- [Vue.js Team](https://vuejs.org) - Framework JavaScript progresivo
- [Chart.js Contributors](https://www.chartjs.org) - Gráficos hermosos
- [Tailwind CSS Team](https://tailwindcss.com) - Utilidades CSS
- [Vite](https://vitejs.dev) - Build tool ultrarrápido
- Comunidad Open Source

---

## 📞 Soporte

Para preguntas, bugs o solicitudes de características:

- **Email**: ed.cerpa@duocuc.cl
- **Documentación**: Revisa los archivos en la carpeta raíz
- **Issues**: Contacta al equipo de desarrollo

---

## 🔐 Seguridad

Si descubres alguna vulnerabilidad de seguridad, por favor envía un email a ed.cerpa@duocuc.cl en lugar de usar el issue tracker público.

---

<div align="center">

**Desarrollado con ❤️ para edisinh0**

[![Made with Laravel](https://img.shields.io/badge/Made%20with-Laravel-red.svg)](https://laravel.com)
[![Made with Vue.js](https://img.shields.io/badge/Made%20with-Vue.js-green.svg)](https://vuejs.org)
[![Powered by Docker](https://img.shields.io/badge/Powered%20by-Docker-blue.svg)](https://www.docker.com/)

[⬆ Volver arriba](#-taskflow---sistema-de-gestión-de-flujos-de-trabajo)

</div>
