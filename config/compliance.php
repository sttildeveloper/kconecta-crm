<?php

return [
    'account_deletion' => [
        /*
         * Records whose final retention policy still requires a business/legal
         * decision stay untouched unless an explicit environment value changes
         * the policy to "delete". Supported values: retain, delete.
         */
        'related_records' => [
            'ratings' => env('ACCOUNT_DELETION_RATINGS_POLICY', 'retain'),
            'work_codes' => env('ACCOUNT_DELETION_WORK_CODES_POLICY', 'retain'),
            'tickets' => env('ACCOUNT_DELETION_TICKETS_POLICY', 'retain'),
            'messages' => env('ACCOUNT_DELETION_MESSAGES_POLICY', 'retain'),
            'audits' => env('ACCOUNT_DELETION_AUDITS_POLICY', 'retain'),
        ],
    ],

    'content_safety' => [
        'report_reasons' => [
            'impersonation',
            'fraud',
            'harassment',
            'discrimination',
            'sexual_content',
            'illegal_service',
            'spam',
            'personal_data',
            'intellectual_property',
            'other',
        ],
        'report_statuses' => ['pending', 'reviewing', 'resolved', 'rejected'],
    ],

    'legal_acceptance' => [
        // Coordinate this flag with a mobile release before enabling it.
        'required_on_registration' => env('LEGAL_ACCEPTANCE_REQUIRED_ON_REGISTRATION', false),
        'documents' => [
            'terms' => env('LEGAL_TERMS_VERSION'),
            'privacy' => env('LEGAL_PRIVACY_VERSION'),
        ],
    ],

    'cookies' => [
        'consent_version' => env('COOKIE_CONSENT_VERSION', '1'),
    ],

    'retention' => [
        'logs_days' => (int) env('RETENTION_LOGS_DAYS', 14),
        'metrics_days' => env('RETENTION_METRICS_DAYS'),
        'ip_days' => env('RETENTION_IP_DAYS'),
        'tickets_days' => env('RETENTION_TICKETS_DAYS'),
        'audits_days' => env('RETENTION_AUDITS_DAYS'),
        'backups' => [
            'hostinger_automatic_days' => 14,
            'routine_manual_days' => 30,
            'exceptional_migration_days' => 90,
        ],
    ],
];
