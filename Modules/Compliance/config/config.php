<?php

return [
    'name' => 'Compliance',

    'thresholds' => [
        'product_compliance_default_days'  => env('COMPLIANCE_PRODUCT_DEFAULT_DAYS', 90),
        'vendor_certificate_gmp_days'      => env('COMPLIANCE_VENDOR_GMP_DAYS', 180),
        'vendor_certificate_default_days'  => env('COMPLIANCE_VENDOR_DEFAULT_DAYS', 60),
        'batch_expiry_days'                => env('COMPLIANCE_BATCH_DAYS', 90),
        'critical_days'                    => env('COMPLIANCE_CRITICAL_DAYS', 7),

        'employee_health_record_days'          => env('COMPLIANCE_EMPLOYEE_HEALTH_DAYS', 30),
        'employee_health_record_critical_days' => env('COMPLIANCE_EMPLOYEE_HEALTH_CRITICAL_DAYS', 15),
    ],
];
