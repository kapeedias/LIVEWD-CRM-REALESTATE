<?php
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

        $subject = "Password Reset Request";
        $text = "Hello $toName,\nClick the following link to reset your password:\n$resetLink";
        $html = "
            <h3>Hello $toName,</h3>
            <p>You requested a password reset. Click the link below:</p>
            <p><a href='$resetLink'>Reset Password</a></p>
            <p>If you didn’t request this, just ignore this message.</p>
        ";

        $payload = [
            'FromEmail' => $this->fromEmail,
            'FromName'  => $this->fromName,
            'Subject'   => $subject,
            'Text-part' => $text,
            'Html-part' => $html,
            'Recipients'=> [['Email' => $toEmail, 'Name' => $toName]]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
