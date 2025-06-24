<?php
// logout.php

// Start session securely
session_start();

// Check if user is logged in (basic validation)
if (
    !isset($_SESSION['user_id']) || 
    empty($_SESSION['user_id']) || 
    !isset($_SESSION['user_email']) || 
    empty($_SESSION['user_email'])
) {
    // No active session - redirect immediately
    header("Location: login.php");
    exit;
}

// Optional: Log logout activity if User class and PDO are available
try {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/classes/User.php';

    $pdo = Database::getInstance();
    $userObj = new User($pdo);

    $userId = $_SESSION['user_id'];
    if ($userId) {
        $userObj->logActivity($userId, "User logged out", "Logout");
    }
} catch (Exception $e) {
    // Log error but continue logout
    error_log("Logout logging failed: " . $e->getMessage());
}

// Clear all session variables
$_SESSION = [];

// Destroy session cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
