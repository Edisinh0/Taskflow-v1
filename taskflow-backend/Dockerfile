# Archivo: /Taskflow-Icontel/taskflow-backend/Dockerfile

FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    shadow

# Instalar extensiones PHP requeridas por Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Instalar Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configurar PHP para producción
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Optimizaciones PHP
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Copiar Composer desde imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivo .env
COPY .env .env

# Copiar archivos de dependencias primero (para cachear layer)
COPY composer.json composer.lock ./
COPY package.json package*.json ./

# Instalar dependencias PHP (sin archivos de desarrollo en producción)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Instalar dependencias Node (si existen)
RUN npm ci --only=production || npm install --only=production || echo "No npm dependencies"

# Copiar el resto de la aplicación
COPY . .

# Completar instalación de Composer
RUN composer dump-autoload --optimize --classmap-authoritative

# Compilar assets de Vue (si aplica)
RUN npm run build || echo "No build script found, skipping..."

# Crear usuario www-data con UID/GID específicos
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Crear carpetas de almacenamiento necesarias
RUN mkdir -p /var/www/html/storage/app/public/progress-attachments \
    && mkdir -p /var/www/html/storage/app/public/attachments

# Crear symlink storage -> public/storage (necesario para servir archivos)
# Lo hacemos ANTES de cambiar a www-data para tener permisos root
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Cambiar al usuario www-data
USER www-data

# Exponer puerto PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
