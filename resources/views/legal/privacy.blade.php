@extends('legal.layout', ['title' => 'Politica de Privacidad'])

@section('content')
    <h2>1. Responsable del tratamiento</h2>
    <p><strong>{{ $legalConfig['responsible_name'] }}</strong></p>
    <ul>
        <li>NIF/CIF: {{ $legalConfig['responsible_tax_id'] }}</li>
        <li>Domicilio: {{ $legalConfig['responsible_address'] }}</li>
        <li>Contacto privacidad: {{ $legalConfig['privacy_contact_email'] }}</li>
    </ul>

    <h2>2. Datos recopilados</h2>
    <ul>
        <li>Datos de cuenta: nombre, correo electronico, telefono, tipo de usuario.</li>
        <li>Datos de uso: eventos funcionales necesarios para operar la plataforma (por ejemplo, metricas de visitas y clicks).</li>
        <li>Datos tecnicos basicos para seguridad y operacion (logs, direccion IP, agente de usuario).</li>
    </ul>

    <h2>3. Finalidades del tratamiento</h2>
    <ul>
        <li>Autenticacion y gestion de cuenta.</li>
        <li>Prestacion de servicios del CRM y app movil.</li>
        <li>Seguridad, prevencion de fraude y soporte tecnico.</li>
        <li>Cumplimiento de obligaciones legales aplicables.</li>
    </ul>

    <h2>4. Base legal</h2>
    <p>Tratamos datos para ejecutar la relacion contractual con el usuario, por interes legitimo en seguridad y operacion y, cuando corresponda, por cumplimiento de obligaciones legales.</p>

    <h2>5. Conservacion de datos</h2>
    <p>{{ $legalConfig['retention_note'] }}</p>

    <h2>6. Cesiones y encargados</h2>
    <p>Podemos usar proveedores tecnologicos para hosting, correo transaccional y operacion tecnica, bajo medidas contractuales y de seguridad adecuadas.</p>

    <h2>7. Derechos del usuario</h2>
    <p>Puede solicitar acceso, rectificacion, eliminacion y otros derechos aplicables por el canal de privacidad indicado.</p>

    <h2>8. Contacto</h2>
    <p>Correo de privacidad: <strong>{{ $legalConfig['privacy_contact_email'] }}</strong></p>
@endsection

