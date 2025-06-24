<?php
// helpers.php

function sanitizeInput(array $input, array $allowedFields): array {
    $clean = [];

    foreach ($allowedFields as $field => $type) {
        $value = trim($input[$field] ?? '');

        switch ($type) {
            case 'email':
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format for $field.");
                }
                break;

            case 'text':
                // Basic alphanumeric text, strip tags, prevent XSS
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                break;

            case 'int':
                if (!ctype_digit($value)) {
                    throw new Exception("Invalid integer value for $field.");
                }
                $value = (int)$value;
                break;

            case 'password':
                // Don't sanitize passwords (to preserve special chars), just trim
                $value = $input[$field] ?? '';
                break;

            default:
                throw new Exception("Unknown validation type: $type");
        }

        $clean[$field] = $value;
    }

    return $clean;
}

function secureSessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();

        // Prevent JavaScript access to session cookie
        ini_set('session.cookie_httponly', 1);

        // Send cookie only over HTTPS
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

        // Enforce strict session handling
        ini_set('session.use_strict_mode', 1);

        // Add SameSite policy
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

function isSessionHijacked(): bool {
    if (!isset($_SESSION['user_ip'], $_SESSION['user_agent'])) {
        return true;
    }

    $ipCheck = $_SESSION['user_ip'] === ($_SERVER['REMOTE_ADDR'] ?? '');
    $agentCheck = $_SESSION['user_agent'] === ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return !($ipCheck && $agentCheck);
}

function enforceSessionSecurity(): void {
    $timeoutDuration = 1800; // 30 minutes

    if (isSessionHijacked() || (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeoutDuration)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }

    $_SESSION['last_activity'] = time();
}
