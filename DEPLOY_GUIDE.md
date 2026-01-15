# Guía de Despliegue en VPS (Ubuntu + Docker) 🚀

Esta guía te llevará paso a paso para desplegar **TaskFlow** en tu servidor VPS usando Docker y GitHub Actions.

## 1. Preparar el Servidor VPS

Conéctate a tu servidor via SSH y ejecuta estos comandos para instalar Docker y Git:

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Dar permisos a tu usuario para usar Docker (evita usar sudo siempre)
sudo usermod -aG docker $USER
newgrp docker

# Instalar Docker Compose (versión plugin)
sudo apt install docker-compose-plugin -y

# Verificar instalación
docker compose version
```

## 2. Configurar GitHub Actions (Secretos)

Para que GitHub pueda entrar a tu servidor y actualizar la app, necesitas guardar las llaves.

1.  Ve a tu repositorio en GitHub -> **Settings** -> **Secrets and variables** -> **Actions**.
2.  Crea los siguientes **New Repository Secrets**:

| Nombre Secret | Valor |
|--------------|-------|
| `VPS_HOST` | La dirección IP de tu servidor (ej: `192.168.1.50`) |
| `VPS_USER` | Tu usuario SSH (ej: `root` o `ubuntu`) |
| `VPS_SSH_KEY` | Tu llave privada SSH. (Ver abajo cómo generarla si no tienes) |

> **¿Cómo generar una llave SSH para GitHub?**
> En tu PC local (NO en el servidor), ejecuta: `ssh-keygen -t ed25519 -C "github-actions"`.
> Copia el contenido de la llave **pública** (`.pub`) y agrégala al archivo `~/.ssh/authorized_keys` **en tu SERVIDOR**.
> Copia el contenido de la llave **privada** (sin extensión) y pégala en el secreto `VPS_SSH_KEY` de GitHub.

## 3. Primer Despliegue (Configuración Inicial)

La primera vez, como no tenemos el archivo `.env` configurado con las contraseñas reales de base de datos, es mejor clonar y configurar manualmente.

**En tu servidor VPS:**

```bash
# 1. Clonar el repo
git clone https://github.com/Edisinh0/Taskflow-Icontel.git
cd Taskflow-Icontel

# 2. Configurar backend
cd taskflow-backend
cp .env.example .env
nano .env
# IMPORTANTE: Cambia DB_HOST=db, DB_PASSWORD=secret, etc.
# DB_HOST debe ser 'db' (nombre del servicio en docker-compose)

# 3. Guardar y Salir (Ctrl+O, Enter, Ctrl+X) y volver a raíz
cd ..

# 4. Lanzar por primera vez
docker compose -f docker-compose.prod.yml up -d --build
```

Si todo sale bien, verás los contenedores corriendo con `docker compose ps`.

## 4. Despliegues Futuros (Automático)

A partir de ahora, cada vez que quieras actualizar la web:

1.  Ve a la pestaña **Actions** en GitHub.
2.  Selecciona **"Deploy to VPS"** en la izquierda.
3.  Haz clic en **"Run workflow"**.

¡GitHub se conectará, bajará los cambios, reconstruirá los contenedores y lanzará las migraciones automáticamente!

---

### Solución de Problemas Comunes

*   **Error 500 / Permisos**:
    Ejecuta en el servidor:
    ```bash
    docker compose -f docker-compose.prod.yml exec backend chown -R www-data:www-data /var/www/html/storage
    ```

*   **Ver Logs**:
    ```bash
    docker compose -f docker-compose.prod.yml logs -f
    ```
