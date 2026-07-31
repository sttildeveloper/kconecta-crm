<?php

return [
    'placeholder_email_domain' => env('PROVIDER_IMPORT_PLACEHOLDER_DOMAIN', 'import.kconecta.local'),

    /*
    |--------------------------------------------------------------------------
    | Specialty aliases
    |--------------------------------------------------------------------------
    |
    | Keys and values are normalized internally, so accents and case are not
    | significant. Each CSV label or keyword can point to one or many catalog
    | names expected in `service_type`.
    |
    */
    'specialty_aliases' => [
        'pintura' => ['Pintura'],
        'pintores' => ['Pintura'],
        'reformas' => ['Reformas'],
        'reformas integrales' => ['Reformas integrales', 'Reformas'],
        'electricidad' => ['Electricidad'],
        'fontaneria' => ['Fontaneria'],
        'fontanería' => ['Fontaneria'],
        'electricidad y fontaneria' => ['Electricidad', 'Fontaneria'],
        'electricidad y fontanería' => ['Electricidad', 'Fontaneria'],
        'limpieza' => ['Limpieza'],
        'limpieza a domicilio' => ['Limpieza'],
        'jardineria' => ['Jardineria'],
        'jardinería' => ['Jardineria'],
        'fumigacion' => ['Fumigacion', 'Control de plagas'],
        'fumigación' => ['Fumigacion', 'Control de plagas'],
        'control de plagas' => ['Control de plagas', 'Fumigacion'],
        'fumigacion y control de plagas' => ['Fumigacion', 'Control de plagas'],
        'fumigación y control de plagas' => ['Fumigacion', 'Control de plagas'],
        'cerrajeria' => ['Cerrajeria'],
        'cerrajería' => ['Cerrajeria'],
        'mudanzas' => ['Mudanzas'],
        'transporte' => ['Transporte', 'Mudanzas'],
        'mudanzas y transporte' => ['Mudanzas', 'Transporte'],
        'instalaciones' => ['Instalaciones'],
        'mantenimiento' => ['Mantenimiento'],
        'instalaciones y mantenimiento' => ['Instalaciones', 'Mantenimiento', 'Electricidad', 'Fontaneria'],
        'gas natural' => ['Gas'],
        'gas' => ['Gas'],
    ],
];
