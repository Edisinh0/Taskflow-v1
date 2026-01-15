# TaskFlow - Guía de Desarrollo Local

## 🚀 Inicio Rápido

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/taskflow-backend.git
cd taskflow-backend
```

### 2. Configuración inicial automática
```bash
# Dar permisos al script
chmod +x dev.sh

# Ejecutar setup (hace todo automáticamente)
./dev.sh setup
```

Esto hará:
- Crear `.env.local` desde `.env.example`
- Iniciar todos los contenedores Docker
- Instalar dependencias de Composer y NPM
- Generar `APP_KEY`
- Ejecutar migraciones
- Compilar assets

### 3. Acceder a la aplicación
- **Frontend:** http://localhost:8080
- **API:** http://localhost:8080/api
- **Mailhog (emails):** http://localhost:8025

---

## 📋 Comandos Disponibles

```bash
# Iniciar entorno
./dev.sh start

# Detener entorno
./dev.sh stop

# Ver logs
./dev.sh logs

# Acceder a shell del contenedor
./dev.sh shell

# Ejecutar Artisan
./dev.sh artisan migrate
./dev.sh artisan make:model Task

# Ejecutar Composer
./dev.sh composer require package/name

# Ejecutar NPM
./dev.sh npm run dev
./dev.sh npm run build

# Refrescar base de datos
./dev.sh fresh

# Ejecutar tests
./dev.sh test

# Generar nueva APP_KEY
./dev.sh key

# Verificar consistencia de versiones PHP
./dev.sh version
```

---

## 🔧 Configuración Manual (si no usas el script)

### 1. Crear archivo de entorno
```bash
cp .env.example .env.local
nano .env.local  # Editar según necesites
cp .env.local .env
```

### 2. Iniciar contenedores
```bash
docker-compose -f docker-compose.dev.yml up -d
```

### 3. Instalar dependencias
```bash
docker-compose -f docker-compose.dev.yml exec app composer install
docker-compose -f docker-compose.dev.yml exec app npm install
```

### 4. Configurar Laravel
```bash
# Generar APP_KEY
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate

# Ejecutar migraciones
docker-compose -f docker-compose.dev.yml exec app php artisan migrate --seed

# Compilar assets
docker-compose -f docker-compose.dev.yml exec app npm run build
```

---

## 🗄️ Acceso a Bases de Datos

### MariaDB
- **Host:** localhost
- **Puerto:** 3307
- **Base de datos:** taskflow_dev
- **Usuario:** taskflow
- **Contraseña:** password

Conectar con cliente:
```bash
mysql -h 127.0.0.1 -P 3307 -u taskflow -ppassword taskflow_dev
```

### Redis
- **Host:** localhost
- **Puerto:** 6380

---

## 📧 Captura de Emails (Mailhog)

Todos los emails enviados por la aplicación se capturan en Mailhog.

Accede a: http://localhost:8025

---

## 🔍 Debugging

### Ver logs de Laravel
```bash
./dev.sh logs app
```

### Ver logs de Nginx
```bash
./dev.sh logs nginx
```

### Acceder a shell para debugging
```bash
./dev.sh shell
```

### Xdebug
Xdebug está habilitado en el entorno de desarrollo.

Configuración para VS Code (`.vscode/launch.json`):
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

---

## 🚫 Archivos que NO se commitean

Los siguientes archivos están en `.gitignore`:
- `.env` (producción)
- `.env.local` (desarrollo)
- `.env.production` (producción)
- `docker-compose.override.yml`
- `/vendor`
- `/node_modules`
- `nginx/ssl/*.pem`

---

## 🔄 Diferencias entre Desarrollo y Producción

| Aspecto | Desarrollo | Producción |
|---------|-----------|------------|
| Archivo Docker Compose | `docker-compose.dev.yml` | `docker-compose.yml` |
| Dockerfile | `Dockerfile.dev` | `Dockerfile` |
| **Versión PHP** | **8.3** | **8.3** |
| Variables de entorno | `.env.local` | `.env` |
| Puerto HTTP | 8080 | 80 |
| Puerto MariaDB | 3307 | 3306 |
| Puerto Redis | 6380 | 6379 |
| APP_DEBUG | true | false |
| APP_ENV | local | production |
| Cache | Deshabilitado | Habilitado |
| Emails | Mailhog | SMTP real |
| Volúmenes | Hot-reload | Copiados en build |
| Xdebug | ✅ Habilitado | ❌ Deshabilitado |
| OPcache | ❌ Deshabilitado | ✅ Habilitado |

---

## 🔧 Troubleshooting

### El puerto 8080 ya está en uso
```bash
# Cambiar puerto en docker-compose.dev.yml
ports:
  - "8081:80"  # Cambiar 8080 por 8081
```

### Permisos de archivos
```bash
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

### Error: "No application encryption key has been specified"
```bash
# Generar nueva APP_KEY
./dev.sh key

# O manualmente:
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate --show
# Luego copiar la key generada al archivo .env
```

### Limpiar todo y empezar de nuevo
```bash
./dev.sh stop
docker-compose -f docker-compose.dev.yml down -v
./dev.sh setup
```

### Ver qué contenedores están corriendo
```bash
docker-compose -f docker-compose.dev.yml ps
```

---

## 📝 Workflow de Desarrollo

1. **Iniciar entorno:** `./dev.sh start`
2. **Hacer cambios en el código** (se reflejan automáticamente)
3. **Si cambias .env:** `./dev.sh restart`
4. **Si cambias Composer/NPM:** Reinstalar dependencias
5. **Si cambias migraciones:** `./dev.sh artisan migrate`
6. **Hacer commit** (solo código, no archivos de configuración)
7. **Detener entorno:** `./dev.sh stop`

---

## 🚀 Despliegue a Producción

Ver `README.DEPLOY.md` para instrucciones de despliegue al VPS.

**Importante:** Los cambios en desarrollo NO afectan la configuración de producción.
