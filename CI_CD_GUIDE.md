# Guía de Implementación CI/CD para Taskflow

Este documento explica la configuración de **Integración Continua (CI)** implementada en este proyecto.

## 1. ¿Qué es CI/CD?

Imagina que tienes un robot asistente que revisa tu trabajo cada vez que guardas cambios. Eso es CI/CD.

*   **CI (Integración Continua)**: Es el proceso de automatizar la verificación de tu código. Cada vez que subes cambios (Push) a GitHub, un servidor automático descarga tu código, instala las librerías necesarias e intenta "construir" el proyecto para ver si hay errores (pantallas blancas, errores de sintaxtis, etc.).
*   **CD (Entrega/Despliegue Continuo)**: Es el paso siguiente (aún no configurado para un servidor específico aquí). Una vez que el CI dice "todo está verde y correcto", el sistema puede subir automáticamente tu web a internet.

## 2. ¿Cómo funciona en este proyecto?

Hemos configurado **GitHub Actions**, la herramienta nativa de GitHub. Funciona mediante archivos "Workflow" (flujos de trabajo) ubicados en la carpeta `.github/workflows/`.

### Tenemos dos guardianes (Workflows):

#### A. Guardián del Backend (`.github/workflows/backend.yml`)
Este guardián vigila la carpeta `taskflow-backend`. Se despierta cuando modificas código PHP/Laravel.

**Lo que hace paso a paso:**
1.  **Setup PHP**: Instala PHP 8.2 en el servidor de pruebas.
2.  **Copia .env**: Configura las variables de entorno básicas.
3.  **Install Dependencies**: Ejecuta `composer install` para asegurar que todas las librerías de Laravel se descarguen bien (si añadiste una librería nueva y olvidaste subirla, aquí fallará).
4.  **Genera Key**: Configura la clave de seguridad de Laravel.
5.  **Check Syntax**: Revisa todos tus archivos PHP en busca de errores de sintaxis (puntos y comas faltantes, paréntesis sin cerrar).

#### B. Guardián del Frontend (`.github/workflows/frontend.yml`)
Este guardián vigila la carpeta `taskflow-frontend`. Se despierta cuando modificas código Vue/JS.

**Lo que hace paso a paso:**
1.  **Setup Node.js**: Prepara el entorno con Node v18 y v20.
2.  **Install Dependencies**: Ejecuta `npm ci` (una versión estricta de `npm install`) para instalar librerías.
3.  **Build**: Ejecuta `npm run build`. Este es el paso más importante: intenta compilar tu código Vue para producción. Si tienes un error de importación, una variable mal usada o un error de sintaxis en Vue, **este paso fallará y te avisará**.

## 3. ¿Cómo lo uso?

¡Ya está funcionando! No tienes que hacer nada especial más que subir tu código a GitHub.

1.  Haz tus cambios en local.
2.  Haz `git add`, `git commit` y `git push`.
3.  Ve a tu repositorio en GitHub.com.
4.  Verás una pestaña llamada **"Actions"**.
5.  Ahí verás tus flujos ejecutándose (un círculo amarillo girando).
    *   ✅ **Verde (Success)**: Todo está perfecto. Tu código es estable.
    *   ❌ **Rojo (Failure)**: Algo se rompió. GitHub te enviará un correo avisándote. Puedes hacer clic en el error para ver exactamente qué falló (ej: "Sintax error línea 40").

## 4. Próximos Pasos (Hacia el CD - Despliegue)

Actualmente, estos flujos solo **verifican** que el código esté sano. No lo suben a ningún servidor.

Si en el futuro deseas que la aplicación se actualice sola en tu servidor (VPS, AWS, DigitalOcean) cuando el código esté verde, necesitarías:

1.  Tener un servidor configurado con acceso SSH.
2.  Guardar tus credenciales (SSH Key, Host, User) en los "Secrets" de GitHub.
3.  Añadir un paso extra llamado "Deploy" en estos archivos `.yml`.

### Ejemplo conceptual de un paso de Deploy (para el futuro):

```yaml
    - name: Deploy to Server
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/taskflow
          git pull origin main
          ./deploy.sh
```

---
