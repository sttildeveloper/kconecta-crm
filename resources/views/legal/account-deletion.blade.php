@extends('legal.layout', ['title' => 'Eliminacion de Cuenta'])

@section('content')
    <h2>1. Eliminacion desde la app</h2>
    <p>Puede iniciar la eliminacion de su cuenta desde la app movil autenticada usando la opcion de eliminar cuenta del perfil.</p>
    <p>Endpoint API: <code>DELETE /api/me</code> (requiere autenticacion y confirmacion de contrasena).</p>

    <h2>2. Solicitud alternativa por web o correo</h2>
    <p>Si no puede acceder a la app, puede solicitar la baja por el canal de soporte: <strong>{{ $legalConfig['support_contact_email'] }}</strong>.</p>

    <h2>3. Que datos se eliminan o anonimizan</h2>
    <ul>
        <li>Se eliminan o anonimizan datos personales directos de la cuenta (nombre, correo, telefonos, direccion de perfil, foto).</li>
        <li>Se revocan tokens activos para impedir nuevos accesos con la cuenta eliminada.</li>
        <li>En proveedores se eliminan dirección pública, especialidades, servicios legacy, portada, galería, vídeo, visitas y clics.</li>
        <li>Solo se retiran archivos físicos atribuibles de forma exclusiva a esa cuenta.</li>
    </ul>

    <h2>4. Datos retenidos y motivo</h2>
    <p>{{ $legalConfig['retention_note'] }}</p>
    <p>Valoraciones, códigos de trabajo, tickets, mensajes y auditorías siguen políticas configurables. Hasta que exista una decisión empresarial y legal explícita, no se activa su borrado irreversible.</p>

    <h2>5. Plazos de eliminacion</h2>
    @if ((int) $legalConfig['account_deletion_sla_days'] <= 0)
        <p>La desactivacion y revocacion de acceso se aplica de forma inmediata tras confirmar la solicitud en app.</p>
    @else
        <p>La solicitud se procesa en un maximo de {{ (int) $legalConfig['account_deletion_sla_days'] }} dias.</p>
    @endif
    <p>La retencion residual se limita a lo estrictamente necesario por obligaciones legales aplicables.</p>
@endsection
