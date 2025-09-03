<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación</title>
</head>
<body>
    <p>Hola {{ $name }},</p>
    <p>Has sido invitado a unirte a nuestro grupo en Gestion Financiera.</p>
    <p>Puedes aceptar la invitación haciendo clic en el siguiente enlace:</p>
    <p><a href="{{ $link }}">{{ $link }}</a></p>
    <p>Si no reconoces esta invitación, puedes ignorar este correo.</p>
</body>
</html>
