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
    $expectedIp = $_SESSION['user_ip'] ?? null;
    $expectedAgent = $_SESSION['user_agent'] ?? null;

    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (SESSION_ENFORCE_IP_CHECK && $expectedIp !== $currentIp) {
        return true;
    }

    if (SESSION_ENFORCE_UA_CHECK && $expectedAgent !== $currentAgent) {
        return true;
    }

    return false;
}


function enforceSessionSecurity(): void {
    $timeout = defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 1800;
    $redirect = defined('SESSION_REDIRECT_ON_TIMEOUT') ? SESSION_REDIRECT_ON_TIMEOUT : 'login.php?timeout=1';

    $now = time();

    // Hijacking or Timeout check
    if (
        isSessionHijacked() ||
        (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $timeout)
    ) {
        // Log activity (optional)
        if (isset($_SESSION['user_id'])) {
            try {
                $pdo = Database::getInstance();
                $userObj = new User($pdo);
                $reason = isSessionHijacked() ? "Session hijacking suspected" : "Session timed out";
                $userObj->logActivity($_SESSION['user_id'], $reason, 'Forced Logout');
            } catch (Throwable $e) {
                error_log("Session termination log failed: " . $e->getMessage());
            }
        }

        session_unset();
        session_destroy();
        header("Location: $redirect");
        exit;
    }

    // Update last activity
    $_SESSION['last_activity'] = $now;
}

