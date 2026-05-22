@extends('legal.layout', ['title' => 'Terminos y Condiciones'])

@section('content')
    <h2>1. Uso permitido</h2>
    <p>El usuario se compromete a usar {{ $legalConfig['brand_name'] }} conforme a la ley, la buena fe y estos terminos, evitando usos fraudulentos o lesivos.</p>

    <h2>2. Cuenta y seguridad</h2>
    <p>El usuario es responsable de custodiar sus credenciales y de la actividad realizada desde su cuenta.</p>

    <h2>3. Publicaciones y contenidos</h2>
    <p>El usuario garantiza que cuenta con legitimidad sobre la informacion publicada y que no infringe derechos de terceros.</p>

    <h2>4. Responsabilidades</h2>
    <p>{{ $legalConfig['brand_name'] }} actua como plataforma de intermediacion digital y no garantiza resultados comerciales concretos entre usuarios.</p>

    <h2>5. Propiedad intelectual</h2>
    <p>Los elementos de la plataforma (software, marca, contenidos propios) estan protegidos por normativa aplicable. Queda prohibida su explotacion no autorizada.</p>

    <h2>6. Suspension o cancelacion</h2>
    <p>Podemos suspender o cancelar cuentas ante incumplimientos, riesgos de seguridad o requerimientos legales.</p>

    <h2>7. Modificaciones</h2>
    <p>Estos terminos pueden actualizarse para reflejar cambios legales o funcionales. La fecha de actualizacion se publica en esta pagina.</p>

    <h2>8. Jurisdiccion y ley aplicable</h2>
    <p>La relacion se regira por la normativa aplicable en {{ $legalConfig['jurisdiction'] }}, salvo disposiciones imperativas en contrario.</p>
@endsection

