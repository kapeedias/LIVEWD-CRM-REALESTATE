<?php

require_once __DIR__ . '/../vendor/autoload.php';  // adjust path

use \Mailjet\Client;
use \Mailjet\Resources;

class Mailer {
    private $apiKey;
    private $apiSecret;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        // Ensure config is loaded first
        if (!defined('MAILJET_API_KEY') || !defined('MAILJET_SECRET_KEY')) {
            throw new Exception("Mailjet API keys not defined.");
        }

        $this->apiKey    = MAILJET_API_KEY;
        $this->apiSecret = MAILJET_SECRET_KEY;

        // Optionally define FROM address/name as constants too
        $this->fromEmail = defined('MAILJET_FROM_EMAIL') ? MAILJET_FROM_EMAIL : 'no-reply@yourdomain.com';
        $this->fromName  = defined('MAILJET_FROM_NAME')  ? MAILJET_FROM_NAME  : 'YourAppName';
    }

    public function sendResetPasswordEmail($toEmail, $toName, $resetToken) {
            $resetLink = "https://app.livewd.ca/reset-password.php?token=" . urlencode($resetToken);

            $subject = "Zentra - Password Reset Request";
            $text = "Hello $toName,\nClick the following link to reset your password:\n$resetLink";
            $html = "
                <h3>Hello $toName,</h3>
                <p>You requested a password reset. Click the link below:</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>If you didn’t request this, just ignore this message.</p>
            ";

            $payload = json_encode([
                'Messages' => [[
                    'From' => [
                        'Email' => $this->fromEmail,
                        'Name' => $this->fromName
                    ],
                    'To' => [[
                        'Email' => $toEmail,
                        'Name' => $toName
                    ]],
                    'Subject' => $subject,
                    'TextPart' => $text,
                    'HTMLPart' => $html
                ]]
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("Mailjet Error ($httpCode): $response");
                return false;
            }

            return true;
        }


public function sendWelcomeAndVerificationEmail($toEmail, $toName, $activationCode) {
    $verifyLink = "https://app.livewd.ca/verify-email.php?code=" . urlencode($activationCode);

    $subject = "Welcome to Zentra! Please Confirm Your Email";
    $text = "Hello $toName,\n\nWelcome to Zentra!\n\nPlease confirm your email by visiting the following link:\n$verifyLink\n\nThank you!";
    $html = "
        <h2>Welcome to Zentra, $toName!</h2>
        <p>We're excited to have you join us.</p>
        <p>Please verify your email by clicking the link below:</p>
        <p><a href='$verifyLink' style='background-color:#0066cc;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;'>Verify My Email</a></p>
        <br />
        <p>If you didn’t create this account, you can ignore this message.</p>
        <p><strong>- Zentra Team</strong></p>
    ";

    $payload = json_encode([
        'Messages' => [[
            'From' => [
                'Email' => $this->fromEmail,
                'Name' => $this->fromName
            ],
            'To' => [[
                'Email' => $toEmail,
                'Name' => $toName
            ]],
            'Subject' => $subject,
            'TextPart' => $text,
            'HTMLPart' => $html
        ]]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Mailjet Welcome Email Error ($httpCode): $response");
        return false;
    }

    return true;
}


    }

