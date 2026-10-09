<?php

return [
    'host' => (string)(getenv('SMTP_HOST') ?: ''),
    'port' => (int)(getenv('SMTP_PORT') ?: 587),
    'username' => (string)(getenv('SMTP_USERNAME') ?: ''),
    'password' => (string)(getenv('SMTP_PASSWORD') ?: ''),
    'encryption' => strtolower((string)(getenv('SMTP_ENCRYPTION') ?: 'tls')),
    'from_email' => (string)(getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_USERNAME') ?: ''),
    'from_name' => (string)(getenv('SMTP_FROM_NAME') ?: 'Chemical Connect'),
    'app_url' => rtrim((string)(getenv('APP_URL') ?: 'http://localhost/chemical-connect'), '/'),
];