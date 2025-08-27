# Gestión Financiera

Sistema construido con Laravel para administrar gastos compartidos entre
usuarios. Permite registrar grupos, invitar participantes, registrar
gastos y saldar deudas mediante pagos aprobados.

## Instalación

Este proyecto utiliza **PostgreSQL** como motor de base de datos.

1. Clonar el repositorio y entrar al directorio del proyecto.
2. Instalar dependencias de PHP y JavaScript:
   ```bash
   composer install
   npm install
   ```
3. Copiar el archivo de ejemplo y generar la clave de la aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configurar la conexión a PostgreSQL en el archivo `.env` y ejecutar las migraciones:
   ```bash
   php artisan migrate
   ```
5. Levantar el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

### PostgreSQL con Docker Compose

Para levantar una instancia local de PostgreSQL se puede utilizar `docker-compose`:

```yaml
version: "3.9"
services:
  db:
    image: postgres:15
    environment:
      POSTGRES_DB: gestion_financiera
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    ports:
      - "5432:5432"
    volumes:
      - db-data:/var/lib/postgresql/data
volumes:
  db-data:
```

Ejecutar con:

```bash
docker compose up -d db
```

## Configuración del `.env`

Variables principales:

| Variable        | Descripción                                      |
|-----------------|--------------------------------------------------|
| `APP_NAME`      | Nombre mostrado de la aplicación.                |
| `APP_URL`       | URL base del backend.                            |
| `APP_KEY`       | Clave generada con `php artisan key:generate`.   |
| `DB_CONNECTION` | Motor de base de datos (usar `pgsql`).           |
| `DB_HOST`       | Host del servidor PostgreSQL.                    |
| `DB_PORT`       | Puerto del servidor (por defecto `5432`).        |
| `DB_DATABASE`   | Nombre de la base de datos.                      |
| `DB_USERNAME`   | Usuario con acceso a la base.                    |
| `DB_PASSWORD`   | Contraseña del usuario.                          |

Ejemplo de configuración:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gestion_financiera
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

El proyecto está pensado para PostgreSQL. Ajusta las variables anteriores según tu entorno.

## Comandos básicos

| Comando                     | Descripción                                      |
|----------------------------|--------------------------------------------------|
| `php artisan serve`        | Inicia el servidor HTTP de desarrollo.           |
| `php artisan migrate`      | Ejecuta migraciones de base de datos.            |
| `php artisan queue:listen` | Procesa trabajos en la cola.                     |
| `npm run dev`              | Compila activos front‑end con Vite.              |
| `php artisan test`         | Ejecuta la suite de pruebas.                     |

## Datos de prueba

Para generar datos iniciales, incluyendo un grupo de ejemplo y una invitación,
ejecutar:

```bash
php artisan migrate --seed --class=DevSeeder
```

La consola mostrará un token de invitación, por ejemplo:

```
Token de invitación para newuser@example.com:
4f3b59d2c4e148d9a5b2bdf5b6c177a3e0b8b50d71675dc1a1e4b42e2c596ef8
```

Con este token se puede registrar un usuario con el endpoint:

```http
POST /api/auth/register
{
  "name": "Nuevo Usuario",
  "email": "newuser@example.com",
  "password": "secret1234",
  "password_confirmation": "secret1234",
  "invitation_token": "4f3b59d2c4e148d9a5b2bdf5b6c177a3e0b8b50d71675dc1a1e4b42e2c596ef8"
}
```

## Documentación de la API

La documentación completa de los endpoints se encuentra en [docs/API.md](docs/API.md).
Para integrar con clientes externos se provee una especificación OpenAPI en [docs/api.yaml](docs/api.yaml)
y una colección de solicitudes lista para importar en [docs/api.http](docs/api.http).

## Endpoints principales

Todos los endpoints se exponen bajo `/api` y requieren autenticación con
Laravel Sanctum salvo donde se indique.

### Autenticación

- `POST /api/auth/register` – registrar usuario.
- `POST /api/auth/login` – iniciar sesión y obtener token.
- `POST /api/auth/logout` – cerrar sesión (requiere auth).

### Invitaciones

- `GET /api/invitations/token/{token}` – verifica un token (público).
- `GET /api/invitations` – lista invitaciones del usuario o de sus grupos.
- `POST /api/invitations` – crea una invitación para un grupo.
- `GET /api/invitations/{id}` – muestra detalles de una invitación.
- `DELETE /api/invitations/{id}` – marca la invitación como expirada.
- `POST /api/invitations/accept` – acepta una invitación mediante token.

### Gastos

- `GET /api/expenses` – lista gastos donde el usuario participa.
- `POST /api/expenses` – registra un nuevo gasto y sus participantes.
- `GET /api/expenses/{id}` – muestra un gasto específico.
- `PUT /api/expenses/{id}` – actualiza el gasto (pagador o participante).
- `DELETE /api/expenses/{id}` – elimina el gasto (pagador).
- `POST /api/expenses/{id}/approve` – pagador aprueba el gasto.

### Pagos

- `GET /api/payments/due` – resumen de deudas pendientes.
- `GET /api/payments` – lista pagos enviados o recibidos.
- `POST /api/payments` – crea un pago para saldar deudas.
- `GET /api/payments/{id}` – muestra detalles de un pago.
- `PUT /api/payments/{id}` – actualiza un pago pendiente (pagador).
- `POST /api/payments/{id}/approve` – receptor confirma y cierra el pago.
- `POST /api/payments/{id}/reject` – receptor rechaza el pago y libera deudas.

## Flujos de trabajo

### Invitaciones
1. Un propietario o administrador del grupo crea una invitación.
2. El invitado verifica el token (`GET /api/invitations/token/{token}`).
3. Estando autenticado, acepta la invitación con el token recibido.

### Gastos
1. El pagador registra el gasto indicando participantes y montos.
2. Opcionalmente adjunta ticket e inicia el proceso OCR.
3. El pagador aprueba el gasto para dejarlo listo para cobro.

### Pagos
1. Un participante selecciona sus deudas y crea un pago hacia el pagador.
2. El pagador revisa y aprueba o rechaza el pago.
3. Al aprobarse se marcan las deudas como pagadas; si se rechaza se liberan.

