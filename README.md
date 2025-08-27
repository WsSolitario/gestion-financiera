# Gestión Financiera

Sistema construido con Laravel para administrar gastos compartidos entre
usuarios. Permite registrar grupos, invitar participantes, registrar
gastos y saldar deudas mediante pagos aprobados.

## Instalación

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
4. Si se usa la base de datos SQLite por defecto, crear el archivo
   `database/database.sqlite` y ejecutar las migraciones:
   ```bash
   php artisan migrate
   ```
5. Levantar el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

## Configuración del `.env`

Variables principales:

| Variable        | Descripción                                      |
|-----------------|--------------------------------------------------|
| `APP_NAME`      | Nombre mostrado de la aplicación.                |
| `APP_URL`       | URL base del backend.                            |
| `APP_KEY`       | Clave generada con `php artisan key:generate`.   |
| `DB_CONNECTION` | Motor de base de datos (por defecto `sqlite`).   |

Para SQLite basta con crear el archivo de base de datos mencionado
arriba. Si se usa MySQL u otro motor, configurar host, puerto, usuario y
contraseña correspondientes.

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

