<?php

return [
    'admin_audit_retention_days' => (int) env('ADMIN_AUDIT_RETENTION_DAYS', 365),
    'admin_two_factor_ttl_minutes' => (int) env('ADMIN_TWO_FACTOR_TTL_MINUTES', 10),
    'admin_two_factor_max_attempts' => (int) env('ADMIN_TWO_FACTOR_MAX_ATTEMPTS', 5),
    'admin_two_factor_retention_days' => (int) env('ADMIN_TWO_FACTOR_RETENTION_DAYS', 30),
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),
    'content_security_policy' => env(
        'SECURITY_CONTENT_SECURITY_POLICY',
        "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' ws: wss:",
    ),
];
