# Documentación de la API

Todas las rutas están bajo el prefijo `/api`. A menos que se indique lo contrario, los endpoints requieren autenticación con un token de Laravel Sanctum enviado en el encabezado `Authorization: Bearer {token}`.

La aplicación opera en dos modos controlados por la variable de entorno `MODE_APP`:

- `private`: el registro de usuarios exige un `registration_token` válido.
- `public`: el registro es abierto y no necesita `registration_token`.

Para cambiar el modo edita el archivo `.env`, ajusta `MODE_APP` y ejecuta `php artisan config:clear`.

### GET /api/app-mode
Devuelve el modo actual de la aplicación (`public` o `private`) en la clave `mode`.
No requiere autenticación.

#### Respuesta
```json
{
  "mode": "public"
}
```

## Flujos completos

### Autenticación - Login
#### Solicitud
```http
POST /api/auth/login
{
  "email": "usuario@example.com",
  "password": "secreto"
}
```
#### Respuesta
```json
{
  "message": "Login correcto",
  "token": "TOKEN",
  "user": {
    "id": "UUID",
    "name": "Usuario Ejemplo",
    "email": "usuario@example.com"
  }
}
```

### Grupos - Crear grupo
#### Solicitud
```http
POST /api/groups
{
  "name": "Viaje",
  "description": "Gastos del viaje",
  "profile_picture_url": "https://example.com/logo.png"
}
```
#### Respuesta
```json
{
  "id": "UUID",
  "name": "Viaje",
  "description": "Gastos del viaje",
  "profile_picture_url": "https://example.com/logo.png"
}
```

Si no se envía `profile_picture_url`, la API genera automáticamente un avatar por defecto basado en el nombre del grupo mediante DiceBear.

### Invitaciones - Crear invitación
#### Solicitud
```http
POST /api/invitations
{
  "invitee_email": "amigo@example.com",
  "group_id": "UUID"
}
```
#### Respuesta
```json
{
  "token": "INVITE-TOKEN"
}
```

### Invitaciones - Aceptar invitación
#### Solicitud
```http
POST /api/invitations/accept
{
  "token": "INVITE-TOKEN"
}
```
#### Respuesta
```json
{
  "message": "Unido al grupo"
}
```

### Gastos - Registrar gasto
#### Solicitud
```http
POST /api/expenses
{
  "description": "Cena",
  "total_amount": 100,
  "group_id": "UUID",
  "expense_date": "2024-01-01",
  "has_ticket": false,
  "participants": [{"user_id": "UUID","amount_due": 100}]
}
```
#### Respuesta
```json
{
  "id": "UUID",
  "status": "pending"
}
```

### Pagos - Crear pago
#### Solicitud
```http
POST /api/payments
{
  "group_id": "UUID",
  "from_user_id": "UUID",
  "to_user_id": "UUID",
  "amount": 50,
  "note": "Pago de cena",
  "evidence_url": "https://example.com/recibo.jpg"
}
```
#### Respuesta
```json
{
  "id": "UUID",
  "status": "pending"
}
```

### Notificaciones - Registrar dispositivo
#### Solicitud
```http
POST /api/notifications/register-device
{
  "device_token": "token-ejemplo",
  "device_type": "web"
}
```
#### Respuesta
```json
{
  "message": "Dispositivo registrado"
}
```

## Flujo de registro e invitaciones

1. **Generar una invitación**

   Crea una invitación para un correo y un grupo con `POST /api/invitations`.

   ```http
   POST /api/invitations
   {
     "invitee_email": "nuevo@correo.com",
     "group_id": "UUID"
   }
   ```

   El endpoint devuelve un token de invitación (`token`) que se enviará al invitado.

2. **Registrar un usuario con los tokens**

  Si `MODE_APP=private`, el invitado usa el `registration_token` recibido y opcionalmente el `group_token` para registrarse vía `POST /api/auth/register`. En modo `public` puede registrarse sin `registration_token`.

   ```http
   POST /api/auth/register
   {
     "name": "Nuevo Usuario",
     "email": "nuevo@correo.com",
     "password": "secreto",
     "password_confirmation": "secreto",
     "registration_token": "REGTOKEN",
     "invitation_token": "GROUPTOKEN" // opcional
   }
   ```

   Si el usuario ya existe, omite este paso y continúa con el siguiente.

3. **Aceptar invitación de usuario existente**

   Un usuario ya registrado puede unirse al grupo usando `POST /api/invitations/accept`.

   ```http
   POST /api/invitations/accept
   {
     "token": "TOKEN"
   }
   ```

4. **Tokens enviados cuando no está registrado**

   Si el correo no está asociado a un usuario, la invitación genera **dos** tokens:

  - `registration_token`: para crear la cuenta mediante `POST /api/auth/register` (solo en modo `private`).
   - `group_token` (`invitation_token`): para unirse al grupo después del registro mediante `POST /api/invitations/accept`.

   El cliente debe manejar ambos tokens en el flujo de alta de usuario.

## Autenticación

### POST /api/auth/register
Registra un usuario utilizando un token de registro.

**Body**
- `name` (string, requerido)
- `email` (string, requerido)
- `password` (string, min 8, requerido)
- `password_confirmation` (string, debe coincidir)
- `registration_token` (string, requerido en modo `private`)
- `invitation_token` (string, opcional)
- `profile_picture_url` (url, opcional)
- `phone_number` (string, opcional)

### POST /api/auth/login
Inicia sesión y devuelve un token Sanctum. Usuarios desactivados no pueden iniciar sesión.
Limitado a **5 intentos por minuto** por combinación de IP y correo.

**Body**
- `email` (string, requerido)
- `password` (string, requerido)

### POST /api/auth/logout
Cierra la sesión actual. Agregar `?all=true` para revocar todos los tokens del usuario.

## Usuarios

### GET /api/users/me
Devuelve los datos del usuario autenticado.

### PUT /api/users/me
Actualiza el perfil del usuario.

**Body** (todos opcionales)
- `name`
- `email`
- `profile_picture_url`
- `phone_number`

### PUT /api/users/me/password
Actualiza la contraseña del usuario.

**Body**
- `current_password` (string, requerido)
- `password` (string, min 8, confirmado)

### DELETE /api/users/me
Desactiva la cuenta del usuario y revoca sus tokens.
El usuario no podrá iniciar sesión mientras esté inactivo.
Actualmente no hay un endpoint para reactivar la cuenta; contacta al soporte si necesitas reactivarla.

## Grupos

### GET /api/groups
Lista los grupos a los que pertenece el usuario.

### POST /api/groups
Crea un nuevo grupo.

**Body**
- `name` (string, requerido)
- `description` (string, opcional)

### GET /api/groups/{group}
Muestra los detalles de un grupo y sus miembros.

### PUT /api/groups/{group}
Actualiza nombre o descripción. Requiere rol `owner` o `admin`.

### DELETE /api/groups/{group}
Elimina el grupo. Solo el `owner`.

### POST /api/groups/{group}/members
Agrega un miembro existente al grupo (owner/admin).

**Body**
- `user_id` (uuid, requerido)
- `role` (`member`|`admin`, opcional)

### PUT /api/groups/{group}/members/{user}
Cambia el rol de un miembro. Permite transferir propiedad con `role=owner`.

### DELETE /api/groups/{group}/members/{user}
Elimina a un miembro del grupo.

### GET /api/groups/{group}/balances
Devuelve el balance de cada miembro del grupo.

### POST /api/groups/{group}/settlements/preview
Sugiere transferencias para saldar balances.

## Invitaciones

### GET /api/invitations
Lista invitaciones. Admite `?mine=true` para ver invitaciones dirigidas a mi email y `?groupId=UUID` para filtrar por grupo.

### POST /api/invitations
Crea una invitación para un grupo (owner/admin).

**Body**
- `invitee_email` (string, requerido)
- `group_id` (uuid, requerido)
- `expires_in_days` (int, opcional, por defecto 7)

### GET /api/invitations/{id}
Muestra una invitación específica.

### DELETE /api/invitations/{id}
Marca la invitación como expirada.

### POST /api/invitations/accept
Acepta una invitación mediante token.

**Body**
- `token` (string, requerido)

### GET /api/invitations/token/{token}
Verifica un token de invitación (público, sin autenticación).

## Gastos

### GET /api/expenses
Lista gastos donde el usuario es pagador o participante.
Filtros opcionales: `?groupId`, `?startDate`, `?endDate`.

Cada gasto contiene un campo `status` con los posibles valores:

- `pending`: registrado pero aún no aprobado por el pagador.
- `approved`: aprobado y pendiente de pago por los participantes.
- `rejected`: descartado por el pagador.
- `completed`: todos los participantes han pagado su parte.

### POST /api/expenses
Registra un nuevo gasto con sus participantes.

**Body**
- `description` (string, requerido)
- `total_amount` (number, requerido)
- `group_id` (uuid, requerido)
- `expense_date` (YYYY-MM-DD, requerido)
- `has_ticket` (boolean, requerido)
- `ticket_image_url` (string, requerido si `has_ticket=true`)
- `participants` (array de `{ user_id, amount_due }`, requerido)

### GET /api/expenses/{id}
Muestra un gasto específico.

### PUT /api/expenses/{id}
Actualiza un gasto existente.

**Body** (al menos un campo)
- `description` (string, opcional)
- `total_amount` (number, opcional)
- `expense_date` (YYYY-MM-DD, opcional)
- `has_ticket` (boolean, opcional)
- `ticket_image_url` (string, requerido si `has_ticket=true`)
- `participants` (array de `{ user_id, amount_due }`, opcional)

### DELETE /api/expenses/{id}
Elimina un gasto (pagador).

### POST /api/expenses/{id}/approve
El pagador aprueba el gasto para que se pueda cobrar. Cuando todos los
participantes hayan pagado sus partes, el gasto cambiará automáticamente al
estado `completed`.

## Pagos

### GET /api/payments/due
Resumen de deudas pendientes para el usuario autenticado. Acepta `?group_id`.

### GET /api/payments
Lista pagos enviados o recibidos.
Filtros opcionales: `?status`, `?direction`, `?groupId`, `?startDate`, `?endDate`.

### POST /api/payments
Crea un pago entre dos miembros de un grupo.

**Body**
- `group_id` (uuid, requerido)
- `from_user_id` (uuid, requerido, debe ser el usuario actual)
- `to_user_id` (uuid, requerido)
- `amount` (number, requerido)
- `note` (string, opcional)
- `evidence_url` (url, opcional)
- `payment_method` (`cash`|`transfer`, opcional)

### GET /api/payments/{id}
Muestra detalles de un pago.

### PUT /api/payments/{id}
Actualiza un pago pendiente.

**Body** (al menos un campo)
- `payment_method` (`cash`|`transfer`, opcional)
- `evidence_url` (url, opcional)
- `signature` (string, opcional)

#### Solicitud
```http
PUT /api/payments/{id}
{
  "payment_method": "transfer",
  "evidence_url": "https://example.com/recibo.png",
  "signature": "Pago aprobado"
}
```

### POST /api/payments/{id}/approve
El receptor aprueba el pago y aplica el monto a deudas.

### POST /api/payments/{id}/reject
El receptor rechaza el pago y libera deudas asociadas.

## Pagos recurrentes

### GET /api/recurring-payments
Lista pagos recurrentes creados por el usuario.

### POST /api/recurring-payments
Crea un nuevo pago recurrente para recordar deudas periódicas. Los pagos recurrentes son personales y no se pueden compartir.

**Body**
- `title` (string, requerido)
- `description` (string, requerido)
- `amount_monthly` (number, requerido, monto a pagar cada mes)
- `months` (integer, requerido, duración en meses)
- `start_date` (date, requerido, fecha del primer pago)
- `day_of_month` (integer 1-31, requerido, día del mes para el cobro)
- `reminder_days_before` (integer, requerido, días de anticipación para recordar)

## Notificaciones

### POST /api/notifications/register-device
Registra o actualiza un dispositivo para recibir notificaciones.

**Body**
- `device_token` (string, requerido)
- `device_type` (`android`|`ios`|`web`, requerido)

## Dashboard

### GET /api/dashboard/summary
Resumen de deudas, pagos y actividad reciente. Filtros opcionales: `?groupId`, `?startDate`, `?endDate`.

## Reportes

### GET /api/reports
Reportes agregados de gastos y pagos.
Filtros: `?groupId`, `?startDate`, `?endDate`, `?granularity=day|month|auto`, `?paymentStatus=approved|pending|rejected|any`.

