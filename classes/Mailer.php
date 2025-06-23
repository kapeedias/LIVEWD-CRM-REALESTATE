<?php
// classes/Mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php'; // adjust path as needed

class Mailer {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            // SMTP config
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = SMTP_USER;
            $this->mail->Password = SMTP_PASS;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = SMTP_PORT;

            // Sender info
            $this->mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        } catch (Exception $e) {
            error_log("Mailer Initialization Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function send($to, $subject, $body, $isHTML = true) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
