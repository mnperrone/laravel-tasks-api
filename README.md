# Laravel SSR Challenge

Aplicación REST API basada en Laravel 11 preparada para ejecutarse en contenedores Docker (PHP 8.3, Nginx y PostgreSQL). Esta guía explica cómo levantar el proyecto por primera vez en tu máquina de desarrollo.

## Requisitos

- Docker y Docker Compose instalados
- Opcional: Make (para comandos de conveniencia)
- Puerto 8080 libre para acceder vía navegador

## Preparación (primer arranque)

1. Clona el repositorio y sitúate en la carpeta del proyecto.

2. Copia el archivo de entorno y personaliza si lo deseas:

```bash
cp .env.example .env
```

3. (Opcional) Si quieres usar Makefile para automatizar pasos:

```bash
make install
```

Este comando hace lo siguiente:
- Construye las imágenes Docker necesarias
- Instala dependencias de Composer dentro del contenedor PHP
- Genera claves de aplicación y JWT
- Ejecuta migraciones y seeders

> Si prefieres ejecutar los pasos manualmente, sigue la sección "Arranque manual".

## Arranque rápido con Docker (recomendado)

1. Construye y levanta los contenedores:

```bash
# Construir imágenes
docker-compose build

# Levantar servicios en background
docker-compose up -d
```

2. Instala dependencias de Composer (si no se ejecutó en el paso anterior):

```bash
docker-compose exec php composer install --prefer-dist --no-interaction
```

3. Genera la clave de la aplicación y la clave JWT:

```bash
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret
```

4. Ejecuta migraciones y seeders:

```bash
docker-compose exec php php artisan migrate --force
docker-compose exec php php artisan db:seed --force
```

5. Abre tu navegador en: http://localhost:8080

## Arranque manual (paso a paso)

Si prefieres control completo, estos son los pasos detallados:

```bash
# Construir imágenes
docker-compose build php nginx postgres

# Levantar solo los servicios necesarios
docker-compose up -d postgres php nginx

# Instalar dependencias
docker-compose exec php composer install --prefer-dist --no-interaction

# Copiar .env si aún no está
cp .env.example .env

# Generar claves
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret

# Migrar y seedear
docker-compose exec php php artisan migrate --force
docker-compose exec php php artisan db:seed --force
```

## Nota importante sobre permisos

El contenedor PHP incluye un entrypoint que ajusta automáticamente los permisos de las carpetas necesarias (`storage` y `bootstrap/cache`) al iniciar el contenedor. No es necesario cambiar permisos a mano en la mayoría de los casos; si encuentras errores de permisos, puedes ejecutar:

```bash
# Ver permisos dentro del contenedor PHP
docker-compose exec php bash -lc "ls -la /var/www/html/storage && ls -la /var/www/html/bootstrap/cache"

# Forzar ajuste (si es estrictamente necesario)
docker-compose exec php bash -lc "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && find /var/www/html/storage -type d -exec chmod 775 {} + && find /var/www/html/storage -type f -exec chmod 664 {} +"
```

## Comprobación rápida

- Accede a la raíz de la app en: http://localhost:8080
- Verifica los logs (dentro del contenedor PHP):

```bash
docker-compose exec php bash -lc "tail -n 200 storage/logs/laravel.log"
```

- Verifica estado de contenedores:

```bash
docker-compose ps
```

## API (resumen)

- Autenticación (JWT):
  - POST /api/auth/login
  - POST /api/auth/logout
  - POST /api/auth/refresh
  - GET  /api/auth/me

- Tareas (requieren token):
  - GET    /api/tasks
  - POST   /api/tasks
  - GET    /api/tasks/{id}
  - PUT    /api/tasks/{id}
  - DELETE /api/tasks/{id}
  - POST   /api/tasks/{id}/complete
  - POST   /api/tasks/{id}/incomplete

Consulta ejemplos de uso dentro del proyecto o usa Postman/curl para probar los endpoints.

## Comandos Make disponibles

```bash
make help    # ver comandos
make build   # construir imágenes
make up      # levantar contenedores
make down    # parar contenedores
make shell   # abrir shell en el contenedor php
make migrate # ejecutar migraciones
make seed    # ejecutar seeders
make test    # ejecutar tests
```

## Problemas comunes y solución rápida

- Error 500 por permisos en vistas compiladas: revisar permisos de `storage/framework/views` y su propietario. El entrypoint del contenedor PHP debe encargarse de esto al arrancar.
- Error de conexión a la base de datos: asegúrate de que PostgreSQL está arriba y que las credenciales en `.env` coinciden con `docker-compose.yml`.

## Enlaces útiles

- Laravel: https://laravel.com/docs
- JWT Auth package: https://github.com/php-open-source-saver/jwt-auth
