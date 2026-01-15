# Consistencia de Versiones - TaskFlow

## 📋 Versiones de PHP

### Desarrollo
- **Dockerfile.dev:** `php:8.3-fpm-alpine`
- **Versión actual:** PHP 8.3.28
- **Extensiones:** PDO MySQL, mbstring, exif, pcntl, bcmath, gd, Redis, **Xdebug**

### Producción
- **Dockerfile:** `php:8.3-fpm-alpine`
- **Versión:** PHP 8.3.28
- **Extensiones:** PDO MySQL, mbstring, exif, pcntl, bcmath, gd, opcache, Redis

### Composer
- **Requisito:** `^8.2` (acepta 8.2, 8.3 o superior)
- **composer.lock:** Generado con PHP 8.3.28 ✅

---

## ✅ Consistencia Verificada

| Componente | Desarrollo | Producción | Estado |
|------------|-----------|------------|--------|
| **Versión PHP** | 8.3.28 | 8.3.28 | ✅ Idéntico |
| **Base Image** | php:8.3-fpm-alpine | php:8.3-fpm-alpine | ✅ Idéntico |
| **Extensiones Core** | Idénticas | Idénticas | ✅ Idéntico |
| **Composer Lock** | PHP 8.3 | PHP 8.3 | ✅ Compatible |

---

## 🔄 Diferencias Intencionales

### Solo en Desarrollo
- ✅ **Xdebug** - Para debugging
- ✅ **php.ini-development** - Configuración de desarrollo
- ✅ Límites más altos (512M memoria, 100M uploads)
- ✅ Timeouts largos para debugging

### Solo en Producción
- ✅ **OPcache** - Optimización de rendimiento
- ✅ **php.ini-production** - Configuración de producción
- ✅ Límites optimizados para producción

---

## 🚨 Importante

**NUNCA** cambiar la versión de PHP en un solo Dockerfile. Si necesitas actualizar:

1. Actualiza **AMBOS** Dockerfiles simultáneamente
2. Prueba en desarrollo primero
3. Regenera `composer.lock` con la nueva versión
4. Verifica que todos los tests pasen
5. Despliega a producción

---

## 📝 Cómo Verificar Versiones

### Desarrollo
```bash
docker-compose -f docker-compose.dev.yml exec app php -v
```

### Producción (en el VPS)
```bash
docker-compose exec app php -v
```

Ambos deben mostrar la **misma versión**.

---

## 🔧 Si Necesitas Cambiar la Versión de PHP

### 1. Actualizar Dockerfiles
```dockerfile
# En Dockerfile y Dockerfile.dev
FROM php:8.4-fpm-alpine  # Cambiar de 8.3 a 8.4
```

### 2. Actualizar composer.json (si es necesario)
```json
{
    "require": {
        "php": "^8.4"
    }
}
```

### 3. Regenerar composer.lock
```bash
./dev.sh build
./dev.sh start
./dev.sh composer update
```

### 4. Probar
```bash
./dev.sh test
```

### 5. Commitear cambios
```bash
git add Dockerfile Dockerfile.dev composer.json composer.lock
git commit -m "Update PHP to 8.4"
```

---

**Última verificación:** 2025-12-12  
**Versión actual:** PHP 8.3.28  
**Estado:** ✅ Consistente en desarrollo y producción
