<?php

require_once __DIR__ . '/../vendor/autoload.php';

function createAppMailer(): PHPMailer\PHPMailer\PHPMailer
{
    $config = require __DIR__ . '/../config/mail.php';
    foreach (['host', 'username', 'password', 'from_email'] as $requiredSetting) {
        if ($config[$requiredSetting] === '') {
            throw new RuntimeException('Missing SMTP setting: ' . $requiredSetting);
        }
    }
    if (!in_array($config['encryption'], ['tls', 'ssl'], true)) {
        throw new RuntimeException('SMTP_ENCRYPTION must be set to tls or ssl.');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $config['host'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $config['username'];
    $mailer->Password = $config['password'];
    $mailer->Port = $config['port'];
    $mailer->SMTPSecure = $config['encryption'] === 'ssl'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->CharSet = 'UTF-8';
    $mailer->Timeout = 15;
    $mailer->setFrom($config['from_email'], $config['from_name']);

    return $mailer;
}