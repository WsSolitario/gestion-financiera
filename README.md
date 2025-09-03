# Gestión Financiera

Sistema construido con Laravel para administrar gastos compartidos entre
usuarios. Permite registrar grupos, invitar participantes, registrar
gastos, saldar deudas mediante pagos aprobados y configurar pagos
recurrentes personales.

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
| `MODE_APP`      | Modo de la aplicación (`private` o `public`).    |
| `APP_KEY`       | Clave generada con `php artisan key:generate`.   |
| `DB_CONNECTION` | Motor de base de datos (usar `pgsql`).           |
| `DB_HOST`       | Host del servidor PostgreSQL.                    |
| `DB_PORT`       | Puerto del servidor (por defecto `5432`).        |
| `DB_DATABASE`   | Nombre de la base de datos.                      |
| `DB_USERNAME`   | Usuario con acceso a la base.                    |
| `DB_PASSWORD`   | Contraseña del usuario.                          |
| `MODE_APP`      | `private` exige `registration_token`; `public` permite registro abierto. |

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

El modo de la aplicación se controla con `MODE_APP`. Tras cambiarlo en el `.env` ejecuta:

```bash
php artisan config:clear
```

En modo `private` el endpoint `POST /api/auth/register` requiere un `registration_token`. En modo `public` este campo no es necesario y cualquiera puede registrarse.

## Comandos básicos

| Comando                     | Descripción                                      |
|----------------------------|--------------------------------------------------|
| `php artisan serve`        | Inicia el servidor HTTP de desarrollo.           |
| `php artisan migrate`      | Ejecuta migraciones de base de datos.            |
| `php artisan queue:listen` | Procesa trabajos en la cola.                     |
| `npm run dev`              | Compila activos front‑end con Vite.              |
| `php artisan test`         | Ejecuta la suite de pruebas.                     |

## Notificaciones Push

Para enviar notificaciones push se utilizan Firebase Cloud Messaging (FCM) y Apple Push Notification service (APNs).

Variables de entorno:

| Variable          | Descripción                                  |
|-------------------|----------------------------------------------|
| `FCM_SERVER_KEY`  | Clave del servidor para FCM.                 |
| `APN_AUTH_TOKEN`  | Token de autenticación de APNs.              |
| `APN_TOPIC`       | Identificador del tópico de la app en APNs.  |

Ejemplo en el `.env`:

```env
FCM_SERVER_KEY=tu_clave_fcm
APN_AUTH_TOKEN=tu_token_apn
APN_TOPIC=com.example.app
```

Para procesar el job `SendPushNotification` es necesario ejecutar el trabajador de la cola:

```bash
php artisan queue:work
```

Documentación adicional:
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [Apple Push Notification service](https://developer.apple.com/documentation/usernotifications)

## Datos de prueba

Para generar datos iniciales, incluyendo un grupo de ejemplo y una invitación,
ejecutar:

```bash
php artisan migrate --seed --class=DevSeeder
```

La consola mostrará un token de registro y uno de invitación, por ejemplo:

```
Token de registro para newuser@example.com:
REGTOKEN123...
Token de invitación para newuser@example.com:
INVTOKEN456...
```

El `registration_token` solo es necesario cuando `MODE_APP=private`. Con estos tokens se puede registrar un usuario con el endpoint:

```http
POST /api/auth/register
{
  "name": "Nuevo Usuario",
  "email": "newuser@example.com",
  "password": "secret1234",
  "password_confirmation": "secret1234",
  "registration_token": "REGTOKEN123...", // requerido en modo privado
  "invitation_token": "INVTOKEN456..." // opcional
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

### Pagos recurrentes

- `GET /api/recurring-payments` – lista pagos recurrentes propios o compartidos.
- `POST /api/recurring-payments` – crea un pago recurrente y permite compartirlo con otros usuarios.

## Flujos de trabajo

### Registro y creación de grupos
1. Solicitar o recibir un token de invitación.
2. Registrar al usuario con `POST /api/auth/register`.
3. Crear un grupo con `POST /api/groups`.
4. Generar invitaciones con `POST /api/invitations` y enviar los token(s) resultantes.
5. Si la persona invitada aún no está registrada, se le debe proporcionar también un token de registro.

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

