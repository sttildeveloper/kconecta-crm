<?php

return [
    'brand_name' => env('LEGAL_BRAND_NAME', 'Kconecta'),
    'responsible_name' => env('LEGAL_RESPONSIBLE_NAME'),
    'responsible_tax_id' => env('LEGAL_RESPONSIBLE_TAX_ID'),
    'responsible_address' => env('LEGAL_RESPONSIBLE_ADDRESS'),
    'privacy_contact_email' => env('LEGAL_PRIVACY_CONTACT_EMAIL', 'privacy@kconecta.com'),
    'support_contact_email' => env('LEGAL_SUPPORT_CONTACT_EMAIL', 'soporte@kconecta.com'),
    'jurisdiction' => env('LEGAL_JURISDICTION'),
    'infrastructure_location' => env('LEGAL_INFRASTRUCTURE_LOCATION'),
    'account_deletion_sla_days' => (int) env('LEGAL_ACCOUNT_DELETION_SLA_DAYS', 0),
    'retention_note' => env(
        'LEGAL_RETENTION_NOTE',
        'Se conservan datos minimos para cumplimiento legal, fiscal, seguridad y prevencion de fraude durante los plazos legalmente aplicables.'
    ),
    'last_updated' => env('LEGAL_LAST_UPDATED', now()->format('d/m/Y')),
    'deleted_user_email_domain' => env('DELETED_USER_EMAIL_DOMAIN', 'kconecta.local'),
];
