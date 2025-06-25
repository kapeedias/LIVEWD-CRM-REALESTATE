<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Mailer.php';

$mailer = new Mailer();
$sent = $mailer->sendResetPasswordEmail('deeps450@gmail.com', 'Sai Deepak', '0987654321');

echo $sent ? "Email sent!" : "Failed to send.";
    
?>