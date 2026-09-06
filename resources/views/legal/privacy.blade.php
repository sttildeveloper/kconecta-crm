@extends('legal.layout', ['title' => 'Politica de Privacidad'])

@section('content')
    <h2>1. Responsable del tratamiento</h2>
    @if($legalConfig['responsible_name'])<p><strong>{{ $legalConfig['responsible_name'] }}</strong></p>@endif
    <ul>
        @if($legalConfig['responsible_tax_id'])<li>NIF/CIF: {{ $legalConfig['responsible_tax_id'] }}</li>@endif
        @if($legalConfig['responsible_address'])<li>Domicilio: {{ $legalConfig['responsible_address'] }}</li>@endif
        <li>Contacto privacidad: {{ $legalConfig['privacy_contact_email'] }}</li>
    </ul>

    <h2>2. Datos recopilados</h2>
    <ul>
        <li>Datos de cuenta: nombre, correo electronico, telefono, tipo de usuario.</li>
        <li>Datos de uso: eventos funcionales necesarios para operar la plataforma (por ejemplo, metricas de visitas y clicks).</li>
        <li>Datos tecnicos basicos para seguridad y operacion (logs, direccion IP, agente de usuario).</li>
        <li>Perfil profesional, ubicación pública, especialidades, imágenes y vídeo aportados por proveedores.</li>
        <li>Contenido generado por usuarios: valoraciones, mensajes, tickets, denuncias y bloqueos.</li>
        <li>Preferencias de cookies y consentimiento publicitario.</li>
    </ul>

    <h2>3. Finalidades del tratamiento</h2>
    <ul>
        <li>Autenticacion y gestion de cuenta.</li>
        <li>Prestacion de servicios del CRM y app movil.</li>
        <li>Seguridad, prevencion de fraude y soporte tecnico.</li>
        <li>Cumplimiento de obligaciones legales aplicables.</li>
        <li>Publicación de fichas profesionales, contacto entre usuarios y moderación de contenido.</li>
    </ul>

    <h2>4. Base legal</h2>
    <p>Tratamos datos para ejecutar la relacion contractual con el usuario, por interes legitimo en seguridad y operacion y, cuando corresponda, por cumplimiento de obligaciones legales.</p>

    <h2>5. Destinatarios, servicios externos e infraestructura</h2>
    <p>Los datos pueden ser tratados por proveedores de alojamiento, correo transaccional y soporte técnico estrictamente para operar la plataforma.</p>
    @if($legalConfig['infrastructure_location'])<p>Ubicación de infraestructura: {{ $legalConfig['infrastructure_location'] }}.</p>@endif
    <p>Las funciones de mapa y búsqueda de lugares pueden comunicar consultas y datos técnicos a Google Maps/Places. Los enlaces de WhatsApp abren un servicio externo y el usuario decide qué información remite.</p>

    <h2>6. Conservacion de datos y almacenamiento</h2>
    <p>{{ $legalConfig['retention_note'] }}</p>
    <p>Los archivos se almacenan vinculados a su titular. Los logs rotan diariamente. Métricas, IP, tickets y auditorías disponen de políticas separadas que solo se activan tras aprobar un plazo.</p>
    <p>Backups automáticos de Hostinger: máximo 14 días; backups manuales rutinarios: 30 días; backups excepcionales de migración: 90 días.</p>

    <h2>7. Derechos del usuario</h2>
    <p>Puede solicitar acceso, rectificacion, eliminacion y otros derechos aplicables por el canal de privacidad indicado.</p>
    <p>La eliminación de cuenta retira el contenido público, medios, especialidades, dirección, tokens y sesiones. Las denuncias pasan por estados pending, reviewing, resolved o rejected y los usuarios autenticados pueden bloquear o desbloquear a otros.</p>

    <h2>8. Contacto</h2>
    <p>Correo de privacidad: <strong>{{ $legalConfig['privacy_contact_email'] }}</strong></p>
@endsection
