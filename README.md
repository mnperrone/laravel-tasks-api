# Laravel SSR Challenge - IT ROCK

[![Laravel](https://img.shields.io/badge/Laravel-11-ff2d20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ed?logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-37%20passing-brightgreen)](#-pruebas)

> API de tareas con autenticación JWT, cache en Redis y documentación Swagger lista para producción en contenedores.

## 🧭 ¿De qué se trata?
- **Propósito:** centralizar la gestión de tareas personales con roles, colas y caché para respuestas rápidas.
- **Cómo funciona:** los usuarios se autentican vía JWT, consultan tareas cacheadas en Redis, disparan eventos que se registran en cola y consumen la API documentada con Swagger.
- **Stack base:** Laravel 11, PHP 8.3, PostgreSQL, Redis, Nginx y Docker Compose.

## ✨ Highlights
- Login y refresh JWT con rate limiting (5/minuto).
- Cache con etiquetado por usuario (TTL 10 min) e invalidación automática.
- Eventos `TaskCreated` y `TaskCompleted` encolados para logging.
- Recursos y políticas que garantizan autorización fina.
- OpenAPI (L5 Swagger) disponible en `/api/documentation`.

## 🚀 Inicio rápido
1. Clona el repo y copia el entorno: `cp .env.example .env`.
2. Levanta la stack completa: `docker-compose up -d --build`.
3. Instala dependencias y genera claves:
   - `docker-compose exec php composer install`
   - `docker-compose exec php php artisan key:generate`
   - `docker-compose exec php php artisan jwt:secret`
4. Migra y llena datos iniciales:
   - `docker-compose exec php php artisan migrate --force`
   - `docker-compose exec php php artisan db:seed --force`
5. Abre `http://localhost:8080` y revisa la documentación en `http://localhost:8080/api/documentation`.
6. En otra terminal, deja corriendo el worker de colas: `docker-compose exec php php artisan queue:work`.
   - Agrega `--daemon` si prefieres dejarlo en segundo plano, o configura Supervisor dentro del contenedor para tenerlo siempre activo.

> Tip: `make install` automatiza todos los pasos anteriores si tienes Make disponible.

## ⚙️ Setup automático con Make
Si tienes `make` instalado, ejecuta `make install` desde la raíz del proyecto y se encarga de:
- Construir las imágenes Docker necesarias.
- Instalar dependencias de Composer dentro del contenedor PHP.
- Generar las llaves de la aplicación y la clave JWT.
- Ejecutar migraciones y seeders iniciales.

Tras `make install`, únicamente debes iniciar el worker de colas con `docker-compose exec php php artisan queue:work` para procesar los eventos en segundo plano.

## 🔧 Variables clave
| Clave | Descripción |
| --- | --- |
| `JWT_SECRET` | token HS256 usado para emitir access/refresh tokens. |
| `JWT_TTL` / `JWT_REFRESH_TTL` | duración (minutos) de tokens de acceso y refresh. |
| `API_POPULATE_KEY` | API key obligatoria para `/api/tasks/populate` (`X-API-KEY`). |
| `CACHE_STORE=redis` | activa cache etiquetada en Redis para listados de tareas. |
| `QUEUE_CONNECTION=redis` | envía listeners a cola asíncrona (necesita `php artisan queue:work`). |
| `L5_SWAGGER_CONST_HOST` | URL base consumida por la UI de Swagger. |

## 📚 API esencial
- `POST /api/auth/login` · recibirás `access_token` y `refresh_token`.
- `POST /api/auth/refresh` · renueva sesión con rate limit compartido.
- `GET /api/tasks` · paginación, filtros por prioridad y estado.
- `POST /api/tasks` · crea tareas con UUID y prioridad (`low|medium|high`).
- `POST /api/tasks/{id}/complete` / `incomplete` · marcan estados y disparan eventos.

Toda la especificación está en Swagger. Si editas endpoints, regenera con `docker-compose exec php php artisan l5-swagger:generate`.

## 🌐 Integración externa
- Ruta `GET /api/tasks/populate` sincroniza tareas desde https://jsonplaceholder.typicode.com/todos usando `Http::retry` para reintentos seguros.
- Requiere token JWT válido **y** la cabecera `X-API-KEY` que debe coincidir con `API_POPULATE_KEY` en tu `.env`.
- Inserta tareas con UUID propio, prioridad `medium` y evita duplicados reutilizando títulos para el usuario autenticado.
- Ejemplo rápido:
   ```bash
   curl -X GET \
      -H "Authorization: Bearer <ACCESS_TOKEN>" \
      -H "X-API-KEY: ${API_POPULATE_KEY}" \
      http://localhost:8080/api/tasks/populate
   ```
- La respuesta informa cuántos registros nuevos se crearon (`{"inserted": <n>}`); los listeners de eventos seguirán registrando actividad si luego las marcas como completadas.

## 🧪 Pruebas
- Ejecuta todo: `docker-compose exec php php artisan test` (37 tests, 76 assertions).
- Filtra suites: `docker-compose exec php php artisan test --filter=TaskApiTest`.

## 🧰 Extras útiles
- `docker-compose ps` · estado de contenedores.
- `docker-compose exec php php artisan queue:work` · procesa listeners en background (usa `--daemon` o un supervisor para mantenerlo activo).
- `make help` · lista comandos rápidos disponibles.

## 📄 Licencia
Distribuido bajo licencia [MIT](LICENSE).
