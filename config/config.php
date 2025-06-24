<?php

// Turn off displaying errors to users
ini_set('display_errors', 0);

// Enable logging
ini_set('log_errors', 1);

// Set custom log file in the current directory
ini_set('error_log', __DIR__ . '/Zentra_Error_log');

// Optionally set error reporting level (log everything)
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// File: config/config.php
date_default_timezone_set('UTC');

// App Metadata
define('APP_NAME', 'Zentra');
define('SUPPORT_EMAIL', 'support@app.livewd.ca');

// Base Domain URL - strict validation domain
$allowed_domain = 'app.livewd.ca';
$current_domain = $_SERVER['HTTP_HOST'] ?? '';
$error = [];
$success = [];

if (stripos($current_domain, $allowed_domain) === false) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied. Unauthorized domain.');
}

define('BASE_URL', (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $allowed_domain);
define('SITE_URL', BASE_URL . '/');

define('APP_ROOT', dirname(__DIR__));

// Login/Reset URL (can be routed to actual files)
define('LOGIN_URL', SITE_URL . 'login.php');
define('RESET_URL', SITE_URL . 'reset_password.php');
define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minutes
define('SESSION_ENFORCE_IP_CHECK', true);
define('SESSION_ENFORCE_UA_CHECK', true);
define('SESSION_REDIRECT_ON_TIMEOUT', 'login.php?timeout=1');


// SendGrid settings
define('SENDGRID_API_KEY', 'your-sendgrid-api-key');
define('SENDGRID_SENDER_EMAIL', 'no-reply@' . $allowed_domain);
define('SENDGRID_SENDER_NAME', 'Zentra');
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'your_sendgrid_api_key');
define('SMTP_PORT', 587);
define('EMAIL_FROM', 'no-reply@livewd.ca');
define('EMAIL_FROM_NAME', 'Zentra');

// Twilio SMS/WhatsApp settings
define('TWILIO_ACCOUNT_SID', 'your-twilio-account-sid');
define('TWILIO_AUTH_TOKEN', 'your-twilio-auth-token');
define('TWILIO_SMS_FROM', '+1234567890');
define('TWILIO_WHATSAPP_FROM', 'whatsapp:+1234567890');

// Google APIs
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');
define('GOOGLE_CALENDAR_CLIENT_ID', 'your-google-calendar-client-id');
define('GOOGLE_CALENDAR_CLIENT_SECRET', 'your-google-calendar-client-secret');
define('GOOGLE_CALENDAR_REDIRECT_URI', SITE_URL . 'google-calendar-callback.php');
define('GOOGLE_RECAPTCHA_SITE_KEY', '6LfApmsrAAAAAKYYa1iy44IfsO7hp31gPrZh2jvu');
define('GOOGLE_RECAPTCHA_SECRET_KEY', '6LfApmsrAAAAADLc6Gg38GfcZenY3XCSfgjKdaOW');

// Token Expiration
define('TOKEN_EXPIRY_MINUTES', 30);
define('REMEMBER_ME_EXPIRY_DAYS', 7);

// Default User Info
define('DEFAULT_COUNTRY', 'Canada');

// CDN Integration
define('USE_CDN', true); // Toggle CDN usage

// CDN Base URLs
define('CDN_AWS_URL', 'https://your-bucket-name.s3.amazonaws.com');
define('CDN_AZURE_URL', 'https://yourstorageaccount.blob.core.windows.net/container-name');
define('CDN_GCP_URL', 'https://storage.googleapis.com/your-bucket-name');

// Select active CDN
// Options: AWS, AZURE, GCP
define('ACTIVE_CDN', 'AWS');

// Return active CDN base URL
function getCdnBaseUrl()
{
    switch (ACTIVE_CDN) {
        case 'AWS':
            return CDN_AWS_URL;
        case 'AZURE':
            return CDN_AZURE_URL;
        case 'GCP':
            return CDN_GCP_URL;
        default:
            return SITE_URL . 'assets';
    }
}

// Helper to get full path to CDN file
function cdn_asset($path)
{
    return rtrim(getCdnBaseUrl(), '/') . '/' . ltrim($path, '/');
}
// Basic password complexity check
function validatePasswordComplexity($password) {
    if (strlen($password) < 8) return "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) return "Password must include an uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) return "Password must include a lowercase letter.";
    if (!preg_match('/[0-9]/', $password)) return "Password must include a number.";
    return true;
}

function generatePassword(int $length = 21, string $complexity = 'strong', string $customChars = ''): string {
    $charSets = [
        'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
        'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'digits'    => '0123456789',
        'symbols'   => '!@#$%^&*()-_=+[]{}<>?/|~',
    ];

    // Define complexity options
    switch ($complexity) {
        case 'low':
            $chars = $charSets['lowercase'];
            break;
        case 'medium':
            $chars = $charSets['lowercase'] . $charSets['digits'];
            break;
        case 'high':
            $chars = $charSets['lowercase'] . $charSets['uppercase'] . $charSets['digits'];
            break;
        case 'strong':
        default:
            $chars = implode('', $charSets);
            break;
    }

    // Override with custom characters if provided
    if (!empty($customChars)) {
        $chars = $customChars;
    }

    // Shuffle and build password
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }

    return $password;
}

function logAppError($exception) {
    $logFile = __DIR__ . '/Zentra_Error_log';
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] " . $exception->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
}
