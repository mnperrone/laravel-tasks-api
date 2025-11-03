#!/bin/bash
set -e

# Entry point para ajustar permisos en carpetas necesarias antes de iniciar php-fpm.
# Ejecuta chown/chmod de forma segura sólo si las carpetas existen.

APP_DIR="/var/www/html"
STORAGE_DIR="$APP_DIR/storage"
BOOTSTRAP_CACHE_DIR="$APP_DIR/bootstrap/cache"

echo "[entrypoint] ajustando permisos en $STORAGE_DIR y $BOOTSTRAP_CACHE_DIR"

if [ -d "$STORAGE_DIR" ]; then
  chown -R www-data:www-data "$STORAGE_DIR" || true
  find "$STORAGE_DIR" -type d -exec chmod 775 {} + || true
  find "$STORAGE_DIR" -type f -exec chmod 664 {} + || true
fi

if [ -d "$BOOTSTRAP_CACHE_DIR" ]; then
  chown -R www-data:www-data "$BOOTSTRAP_CACHE_DIR" || true
  chmod -R ug+rwx "$BOOTSTRAP_CACHE_DIR" || true
fi

# También asegurar permisos básicos para el proyecto (no forzar en archivos del host)
if [ -d "$APP_DIR" ]; then
  chown -R www-data:www-data "$APP_DIR"/storage "$APP_DIR"/bootstrap/cache || true
fi

# Ejecutar el comando pasado al contenedor (por ejemplo php-fpm)
exec "$@"

