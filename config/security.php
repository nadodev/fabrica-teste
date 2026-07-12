<?php

return [
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),
    'content_security_policy' => env(
        'SECURITY_CONTENT_SECURITY_POLICY',
        "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self' ws: wss:",
    ),
];
