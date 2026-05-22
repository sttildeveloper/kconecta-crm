<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kconecta - Eliminacion de Cuenta</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #1f2937; background: #f8fafc; }
        .wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 4px 18px rgba(0,0,0,.06); }
        h1,h2 { color: #0f172a; }
        nav a { margin-right: 12px; color: #0ea5e9; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        .muted { color: #64748b; font-size: 14px; }
        ul,p,ol { line-height: 1.65; }
        code { background: #e2e8f0; padding: 2px 6px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Eliminacion de Cuenta</h1>
        <p class="muted">Ultima actualizacion: 22/05/2026</p>

        <nav>
            <a href="{{ url('/legal/privacy') }}">Privacidad</a>
            <a href="{{ url('/legal/terms') }}">Terminos</a>
            <a href="{{ url('/legal/account-deletion') }}">Eliminacion de cuenta</a>
        </nav>

        <h2>1. Eliminacion desde la app</h2>
        <p>Puede iniciar la eliminacion de su cuenta desde la app movil autenticada usando la opcion de eliminar cuenta del perfil.</p>
        <p>El endpoint API de eliminacion es <code>DELETE /api/me</code> (requiere autenticacion y confirmacion de contraseña).</p>

        <h2>2. Solicitud alternativa por web/correo</h2>
        <p>Si no puede acceder a la app, contacte por los canales de soporte de Kconecta para solicitar la baja de cuenta asociada a su correo.</p>

        <h2>3. Que datos se eliminan o anonimizan</h2>
        <ul>
            <li>Se eliminan o anonimizan datos personales directos de la cuenta (nombre, correo, telefonos, direccion de perfil, foto).</li>
            <li>Se revocan tokens activos para impedir nuevos accesos con la cuenta eliminada.</li>
        </ul>

        <h2>4. Datos retenidos y motivo</h2>
        <p>Podemos conservar datos estrictamente necesarios de forma minimizada/anonimizada para:</p>
        <ul>
            <li>cumplimiento legal o fiscal,</li>
            <li>seguridad y prevencion de fraude,</li>
            <li>defensa de reclamaciones.</li>
        </ul>

        <h2>5. Plazos (SLA)</h2>
        <p>La desactivacion y revocacion de acceso se aplica de forma inmediata tras confirmar la solicitud en app.</p>
        <p>La purga o retencion residual seguira los plazos legales aplicables.</p>
    </div>
</div>
</body>
</html>
