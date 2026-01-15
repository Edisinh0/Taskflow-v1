# 🚀 Quick Start - Taskflow

## Desarrollo (con Hot Module Replacement)

```bash
./dev.sh
```

Luego abre: **http://localhost:5173**

**Los cambios en Vue se reflejan automáticamente** ✨

---

## Producción (Docker completo)

```bash
cd taskflow-backend
docker-compose up -d
```

Luego abre: **http://localhost**

**Necesitas reconstruir el frontend para ver cambios:**
```bash
docker-compose build frontend
docker-compose up -d frontend
```

---

## Comandos Útiles

### Reiniciar Backend (después de cambios PHP)
```bash
docker-compose restart backend
```

### Ver Logs
```bash
docker-compose logs -f backend
docker-compose logs -f frontend
```

### Ejecutar Migraciones
```bash
docker-compose exec backend php artisan migrate
```

### Detener Todo
```bash
docker-compose down
```

---

## ¿Dónde trabajar?

| Tarea | URL | Recarga automática |
|-------|-----|-------------------|
| Desarrollo Vue | http://localhost:5173 | ✅ Sí (HMR) |
| Desarrollo Backend | http://localhost:5173 | ⚠️ Restart container |
| Pruebas finales | http://localhost | ❌ Rebuild necesario |
| Producción | http://localhost | ❌ Rebuild necesario |

---

📚 **Documentación completa**: Ver [DESARROLLO.md](DESARROLLO.md)
