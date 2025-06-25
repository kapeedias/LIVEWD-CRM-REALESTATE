<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Mailer.php';

$mailer = new Mailer();
$sent = $mailer->sendResetPasswordEmail('info@livewd.ca', 'John Doe', '1234565');

if ($sent) {
    echo "Reset link sent!";
} else {
    echo "Failed to send reset link.";
}
  
?>